<?php

namespace App\Http\Resources;

// Imports: modelo User, petición y JsonResource.
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class UserResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role->value,
            'programa' => $this->programa,
            'semestre' => $this->semestre,
            'creado_en' => $this->created_at?->toIso8601String(),
        ];
    }
}
