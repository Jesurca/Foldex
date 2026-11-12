<?php

namespace App\Http\Resources;

// Imports: modelo Tecnologia, petición y JsonResource.
use App\Models\Tecnologia;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Tecnologia */
class TecnologiaResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
        ];
    }
}
