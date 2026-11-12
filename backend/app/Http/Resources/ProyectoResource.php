<?php

namespace App\Http\Resources;

// Imports: modelo Proyecto, petición, JsonResource y disco de archivos (URL de la portada).
use App\Models\Proyecto;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/**
 * Contrato JSON de un proyecto. `liked` es null para visitantes anónimos;
 * `can` le dice al frontend qué botones mostrar (la API vuelve a validar con la Policy).
 *
 * @mixin Proyecto
 */
class ProyectoResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $usuario = $request->user('sanctum');

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'titulo' => $this->titulo,
            'contexto' => $this->contexto,
            'repositorio_url' => $this->repositorio_url,
            'video_url' => $this->video_url,
            // null si el autor no subió foto: el frontend muestra una ilustración.
            'portada_url' => $this->portada_path ? Storage::disk('public')->url($this->portada_path) : null,
            'periodo' => $this->periodo,
            'materia' => MateriaResource::make($this->whenLoaded('materia')),
            'docente' => DocenteResource::make($this->whenLoaded('docente')),
            'autor' => AutorResource::make($this->whenLoaded('user')),
            'tecnologias' => TecnologiaResource::collection($this->whenLoaded('tecnologias')),
            'likes_count' => (int) ($this->likes_count ?? 0),
            'comentarios_count' => (int) ($this->comentarios_count ?? 0),
            'liked' => $usuario ? (bool) ($this->liked_by_me ?? false) : null,
            'can' => [
                'update' => (bool) $usuario?->can('update', $this->resource),
                'delete' => (bool) $usuario?->can('delete', $this->resource),
            ],
            'publicado_en' => $this->created_at?->toIso8601String(),
        ];
    }
}
