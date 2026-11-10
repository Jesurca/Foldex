<?php

namespace App\Policies;

use App\Models\Proyecto;
use App\Models\User;

class ProyectoPolicy
{
    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Proyecto $proyecto): bool
    {
        return $this->esDuenoOAdmin($user, $proyecto);
    }

    public function delete(User $user, Proyecto $proyecto): bool
    {
        return $this->esDuenoOAdmin($user, $proyecto);
    }

    private function esDuenoOAdmin(User $user, Proyecto $proyecto): bool
    {
        return $user->esAdmin() || $user->id === $proyecto->user_id;
    }
}
