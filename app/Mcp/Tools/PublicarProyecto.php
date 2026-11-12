<?php

namespace App\Mcp\Tools;

use App\Actions\Proyectos\DescargarPortada;
use App\Actions\Proyectos\PublicarProyecto as AccionPublicar;
use App\Actions\Proyectos\ResolverCatalogos;
use App\Http\Requests\Proyectos\StoreProyectoRequest;
use App\Models\Docente;
use App\Models\Materia;
use App\Models\Proyecto;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

/**
 * Publica un proyecto a nombre de la cuenta conectada (la dueña del token), igual que
 * `POST /api/v1/proyectos`: mismas reglas (StoreProyectoRequest) y misma Action.
 * La diferencia es que aquí llegan nombres ("Fabián Suárez", "React") en vez de ids.
 */
#[Name('publicar_proyecto')]
#[Description('Publica el proyecto en el repositorio a nombre del estudiante conectado. Devuelve el enlace de la publicación.')]
class PublicarProyecto extends Tool
{
    public function handle(Request $request, ResolverCatalogos $catalogos, DescargarPortada $descargar, AccionPublicar $publicar): Response
    {
        $autor = $request->user();
        Gate::forUser($autor)->authorize('create', Proyecto::class);

        // Mismo límite que la ruta web (10 publicaciones por minuto por usuario).
        if (RateLimiter::tooManyAttempts("mcp-publicar:{$autor->id}", 10)) {
            return Response::error('Demasiadas publicaciones seguidas. Espera un minuto.');
        }
        RateLimiter::hit("mcp-publicar:{$autor->id}");

        $entrada = $request->validate([
            'repositorio_url' => ['required', 'string'],
            'docente' => ['required', 'string', 'max:150'],
            'titulo' => ['required', 'string'],
            'contexto' => ['required', 'string'],
            'materia' => ['required', 'string', 'max:150'],
            'tecnologias' => ['required', 'array', 'min:1'],
            'tecnologias.*' => ['string', 'max:60'],
            'periodo' => ['nullable', 'string'],
            'video_url' => ['nullable', 'string'],
            'portada_url' => ['nullable', 'string', 'max:500'],
        ]);

        // 1. Nombres → ids del catálogo. Si algo no coincide, se le dan a Claude las opciones válidas.
        $docente = $catalogos->docente($entrada['docente']);
        $materia = $catalogos->materia($entrada['materia']);
        $tecnologias = $catalogos->tecnologias($entrada['tecnologias']);

        $errores = array_filter([
            'docente' => $docente ? null : 'No encontré un único docente con ese nombre. Opciones: '.Docente::query()->orderBy('nombre')->pluck('nombre')->implode('; ').'.',
            'materia' => $materia ? null : 'No encontré una única materia con ese nombre o código. Opciones: '.Materia::query()->orderBy('nombre')->pluck('nombre')->implode('; ').'.',
            'tecnologias' => $tecnologias['ids'] ? null : 'Ninguna tecnología está en el catálogo. Usa los nombres de listar_catalogos.',
        ]);
        if ($errores) {
            throw ValidationException::withMessages($errores);
        }

        // 2. Foto de portada: la que propone Claude o, si es de GitHub, la imagen social del repositorio.
        $urlPortada = $entrada['portada_url'] ?? null;
        if (! $urlPortada && $repo = LeerRepositorio::repoDeGithub($entrada['repositorio_url'])) {
            $urlPortada = "https://opengraph.githubassets.com/1/{$repo}";
        }
        $portada = $urlPortada ? $descargar($urlPortada) : null;

        try {
            // 3. Mismas reglas que el formulario web. Si la portada no pasa, se publica sin ella.
            $datos = [
                'titulo' => trim($entrada['titulo']),
                'contexto' => trim($entrada['contexto']),
                'materia_id' => $materia->id,
                'docente_id' => $docente->id,
                'periodo' => trim($entrada['periodo'] ?? '') ?: self::periodoActual(),
                'repositorio_url' => trim($entrada['repositorio_url']),
                'video_url' => trim($entrada['video_url'] ?? '') ?: null,
                'tecnologias' => array_slice($tecnologias['ids'], 0, 10),
            ];
            $reglas = (new StoreProyectoRequest)->rules();
            $form = new StoreProyectoRequest;

            $avisos = [];
            if ($portada && Validator::make(['portada' => $portada], ['portada' => $reglas['portada']])->fails()) {
                $avisos[] = 'La foto de portada no era una imagen válida (JPG, PNG o WebP, máx. 2 MB, mín. 300 × 150): se publicó sin foto.';
                $portada = null;
            } elseif ($urlPortada && ! $portada) {
                $avisos[] = 'No se pudo descargar la foto de portada: se publicó sin foto.';
            }
            if ($tecnologias['desconocidas']) {
                $avisos[] = 'Se omitieron tecnologías que no están en el catálogo: '.implode(', ', $tecnologias['desconocidas']).'.';
            }

            $validos = Validator::make($datos, collect($reglas)->except('portada')->all(), $form->messages(), $form->attributes())->validate();
            $proyecto = $publicar($autor, [...$validos, 'portada' => $portada]);
        } finally {
            if ($portada) {
                @unlink($portada->getPathname());
            }
        }

        $enlace = url("/proyectos/{$proyecto->slug}");

        return Response::text(implode("\n", [
            "Proyecto publicado: «{$proyecto->titulo}»",
            "Publicado por: {$autor->name} ({$autor->email})",
            "Docente: {$docente->nombre} · Materia: {$materia->nombre} · Periodo: {$proyecto->periodo}",
            "Enlace: {$enlace}",
            ...$avisos,
        ]));
    }

    /** @return array<string, mixed> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'repositorio_url' => $schema->string()->description('Enlace https del repositorio (lo da el estudiante).')->required(),
            'docente' => $schema->string()->description('Nombre del docente que dirigió el proyecto (lo da el estudiante). Debe coincidir con uno de listar_catalogos.')->required(),
            'titulo' => $schema->string()->description('Nombre del proyecto, entre 3 y 150 caracteres.')->required(),
            'contexto' => $schema->string()->description('Descripción en español: problema, solución, funcionamiento y cómo ejecutarlo. Entre 20 y 5000 caracteres.')->required(),
            'materia' => $schema->string()->description('Nombre o código de la materia, de listar_catalogos.')->required(),
            'tecnologias' => $schema->array()->items($schema->string())->description('De 1 a 10 nombres de tecnologías de listar_catalogos.')->required(),
            'periodo' => $schema->string()->description('Periodo académico AAAA-1 o AAAA-2. Si se omite, se usa el periodo actual.'),
            'video_url' => $schema->string()->description('Enlace https opcional a un video (YouTube o Google Drive).'),
            'portada_url' => $schema->string()->description('Enlace https a una imagen JPG/PNG/WebP para la portada. Si se omite y el repositorio es de GitHub, se usa su imagen social.'),
        ];
    }

    /** Enero–junio → AAAA-1; julio–diciembre → AAAA-2 (mismo criterio que el formulario web). */
    public static function periodoActual(): string
    {
        return now()->format('Y').'-'.(now()->month <= 6 ? '1' : '2');
    }
}
