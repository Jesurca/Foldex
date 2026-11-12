<?php

namespace App\Policies;

// Imports: modelos Comentario y User.
use App\Models\Comentario;
use App\Models\User;

/** Permisos sobre un comentario: lo borra su autor o un admin. */
class ComentarioPolicy
{
    public function delete(User $user, Comentario $comentario): bool
    {
        return $user->esAdmin() || $user->id === $comentario->user_id;
    }
}
