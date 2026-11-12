<?php

namespace App\Actions\Auth;

use App\Models\User;

class EmitirToken
{
    /** Devuelve el token en texto plano: solo se muestra una vez. */
    public function __invoke(User $user, ?string $nombre = null): string
    {
        return $user->createToken($nombre ?: 'api')->plainTextToken;
    }
}
