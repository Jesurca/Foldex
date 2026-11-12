<?php

namespace App\Actions\Auth;

// Imports: modelo de usuario (dueño del token).
use App\Models\User;

/** Crea un token personal de Sanctum (Bearer). Lo usan login, registro y "Conéctate por MCP". */
class EmitirToken
{
    /** Devuelve el token en texto plano: solo se muestra una vez. */
    public function __invoke(User $user, ?string $nombre = null): string
    {
        return $user->createToken($nombre ?: 'api')->plainTextToken;
    }
}
