<?php

namespace App\Http\Resources;

// Imports: modelo Materia, petición y JsonResource.
use App\Models\Materia;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Materia */
class MateriaResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'codigo' => $this->codigo,
            'nombre' => $this->nombre,
            'semestre' => $this->semestre,
        ];
    }
}
