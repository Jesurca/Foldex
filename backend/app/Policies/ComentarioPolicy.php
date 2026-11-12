<?php

namespace App\Policies;

use App\Models\Comentario;
use App\Models\User;

class ComentarioPolicy
{
    public function delete(User $user, Comentario $comentario): bool
    {
        return $user->esAdmin() || $user->id === $comentario->user_id;
    }
}
