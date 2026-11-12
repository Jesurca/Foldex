<?php

namespace App\Mcp\Tools;

// Imports: esquema JSON de la herramienta, cliente HTTP (API de GitHub) y clases base de laravel/mcp.
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsOpenWorld;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Throwable;

/**
 * Lee un repositorio público de GitHub (descripción, temas, lenguajes y README) con la API
 * de GitHub, para que Claude redacte el contexto aunque no tenga acceso a internet.
 * Si se define GITHUB_TOKEN en el .env, se usa para no chocar con el límite de 60 consultas/hora.
 */
#[Name('leer_repositorio')]
#[Description('Lee un repositorio público de GitHub: descripción, temas, lenguajes, README y una foto de portada sugerida.')]
#[IsReadOnly]
#[IsOpenWorld]
class LeerRepositorio extends Tool
{
    /** README recortado para no llenar el contexto de Claude. */
    private const MAX_README = 12000;

    public function handle(Request $request): Response
    {
        $datos = $request->validate(
            ['repositorio_url' => ['required', 'string', 'url:https', 'max:500']],
            ['repositorio_url.url' => 'El repositorio debe ser un enlace https:// válido.'],
        );

        $repo = self::repoDeGithub($datos['repositorio_url']);
        if (! $repo) {
            return Response::error('Solo puedo leer repositorios públicos de github.com. Lee tú el repositorio y continúa con publicar_proyecto.');
        }

        try {
            $info = $this->github()->get("repos/{$repo}");
            if ($info->notFound()) {
                return Response::error("No encontré {$repo} en GitHub. ¿Es público y el enlace es correcto?");
            }
            $info->throw();

            $lenguajes = $this->github()->get("repos/{$repo}/languages")->json() ?? [];
            $readme = $this->github()->accept('application/vnd.github.raw')->get("repos/{$repo}/readme");
        } catch (Throwable) {
            return Response::error('GitHub no respondió. Intenta de nuevo o lee tú el repositorio.');
        }

        return Response::json([
            'repositorio' => $repo,
            'nombre' => $info->json('name'),
            'descripcion' => $info->json('description'),
            'temas' => $info->json('topics') ?? [],
            'lenguajes' => array_keys($lenguajes),
            'sitio' => $info->json('homepage'),
            'readme' => $readme->successful() ? Str::limit($readme->body(), self::MAX_README) : null,
            // Imagen social que GitHub genera para cada repositorio (1280 × 640): sirve de portada.
            'portada_sugerida' => "https://opengraph.githubassets.com/1/{$repo}",
        ]);
    }

    /** @return array<string, mixed> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'repositorio_url' => $schema->string()->description('Enlace https del repositorio, p. ej. https://github.com/usuario/proyecto')->required(),
        ];
    }

    /** "https://github.com/ana/app.git" → "ana/app". null si no es un repositorio de GitHub. */
    public static function repoDeGithub(string $url): ?string
    {
        $patron = '#^https://(?:www\.)?github\.com/([A-Za-z0-9-]+)/([A-Za-z0-9._-]+?)(?:\.git)?/?(?:[?\#/].*)?$#';

        return preg_match($patron, trim($url), $m) ? "{$m[1]}/{$m[2]}" : null;
    }

    private function github(): PendingRequest
    {
        return Http::baseUrl('https://api.github.com')
            ->timeout(10)
            ->acceptJson()
            ->withUserAgent(config('app.name'))
            ->when(config('services.github.token'), fn (PendingRequest $h, string $token) => $h->withToken($token));
    }
}
