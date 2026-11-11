<?php

namespace App\Actions\Proyectos;

use App\Models\Proyecto;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PublicarProyecto
{
    /**
     * Crea el proyecto a nombre del autor autenticado y le asocia las tecnologías.
     * El autor nunca sale del body: se pasa explícitamente desde el token.
     *
     * @param  array{titulo: string, contexto: string, materia_id: int, docente_id: int, periodo: string, repositorio_url: string, video_url?: ?string, tecnologias: list<int>}  $datos
     */
    public function __invoke(User $autor, array $datos): Proyecto
    {
        return DB::transaction(function () use ($autor, $datos): Proyecto {
            $proyecto = new Proyecto($datos);
            $proyecto->user()->associate($autor);
            $proyecto->save();
            $proyecto->tecnologias()->sync($datos['tecnologias']);

            return $proyecto;
        });
    }
}
