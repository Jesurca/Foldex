<?php

namespace App\Http\Resources;

use App\Models\Comentario;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Comentario */
class ComentarioResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'contenido' => $this->contenido,
            'autor' => AutorResource::make($this->whenLoaded('user')),
            'can' => [
                'delete' => (bool) $request->user('sanctum')?->can('delete', $this->resource),
            ],
            'creado_en' => $this->created_at?->toIso8601String(),
        ];
    }
}
