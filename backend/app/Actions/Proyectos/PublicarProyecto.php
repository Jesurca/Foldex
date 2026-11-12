<?php

namespace App\Actions\Proyectos;

// Imports: modelos Proyecto y User, archivo subido, transacciones y disco de archivos.
use App\Models\Proyecto;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

/** Publica un proyecto. La comparten POST /api/v1/proyectos (formulario web) y la herramienta MCP publicar_proyecto. */
class PublicarProyecto
{
    /**
     * Crea el proyecto a nombre del autor autenticado y le asocia las tecnologías.
     * El autor nunca sale del body: se pasa explícitamente desde el token.
     * La portada (opcional) se guarda en el disco `public` con nombre aleatorio.
     *
     * @param  array{titulo: string, contexto: string, materia_id: int, docente_id: int, periodo: string, repositorio_url: string, video_url?: ?string, tecnologias: list<int>, portada?: ?UploadedFile}  $datos
     */
    public function __invoke(User $autor, array $datos): Proyecto
    {
        $portada = $datos['portada'] ?? null;
        unset($datos['portada']);

        // `store()` genera un nombre aleatorio con la extensión según el contenido real, no la del usuario.
        $ruta = $portada instanceof UploadedFile ? $portada->store('portadas', 'public') : null;

        try {
            return DB::transaction(function () use ($autor, $datos, $ruta): Proyecto {
                $proyecto = new Proyecto($datos);
                $proyecto->portada_path = $ruta ?: null;
                $proyecto->user()->associate($autor);
                $proyecto->save();
                $proyecto->tecnologias()->sync($datos['tecnologias']);

                return $proyecto;
            });
        } catch (Throwable $e) {
            // Si la base de datos falla, no dejar la imagen huérfana en disco.
            if ($ruta) {
                Storage::disk('public')->delete($ruta);
            }

            throw $e;
        }
    }
}
