<?php

namespace App\Actions\Proyectos;

use App\Models\Proyecto;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Edición de un proyecto por un admin. El autor, el slug y la fecha de publicación no cambian.
 * Si llega una foto nueva reemplaza a la anterior; con `quitar_portada` se elimina.
 */
class ActualizarProyecto
{
    /**
     * @param  array{titulo: string, contexto: string, materia_id: int, docente_id: int, periodo: string, repositorio_url: string, video_url?: ?string, tecnologias: list<int>, portada?: ?UploadedFile, quitar_portada?: bool}  $datos
     */
    public function __invoke(Proyecto $proyecto, array $datos): Proyecto
    {
        $portada = $datos['portada'] ?? null;
        $quitar = (bool) ($datos['quitar_portada'] ?? false);
        unset($datos['portada'], $datos['quitar_portada']);

        $anterior = $proyecto->portada_path;
        $nueva = $portada instanceof UploadedFile ? $portada->store('portadas', 'public') : null;

        try {
            DB::transaction(function () use ($proyecto, $datos, $nueva, $quitar): void {
                $proyecto->fill($datos);
                if ($nueva || $quitar) {
                    $proyecto->portada_path = $nueva ?: null;
                }
                $proyecto->save();
                $proyecto->tecnologias()->sync($datos['tecnologias']);
            });
        } catch (Throwable $e) {
            if ($nueva) {
                Storage::disk('public')->delete($nueva);
            }

            throw $e;
        }

        // La foto vieja se borra solo cuando la base de datos ya guardó el cambio.
        if ($anterior && $anterior !== $proyecto->portada_path) {
            Storage::disk('public')->delete($anterior);
        }

        return $proyecto;
    }
}
