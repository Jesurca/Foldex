<?php

namespace App\Http\Resources;

// Imports: modelo Docente, petición y JsonResource.
use App\Models\Docente;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Docente — el correo no se expone: no hace falta para filtrar ni publicar. */
class DocenteResource extends JsonResource
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
