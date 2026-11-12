<?php

namespace App\Actions\Auth;

// Imports: rol (enum) y modelo de usuario.
use App\Enums\Role;
use App\Models\User;

/** Crea una cuenta con rol admin. La usa POST /api/v1/admin/users (solo un admin puede crear otro). */
class CrearAdmin
{
    /** @param  array{name: string, email: string, password: string, programa?: ?string}  $datos */
    public function __invoke(array $datos): User
    {
        $user = new User($datos);
        $user->role = Role::Admin;
        $user->email_verified_at = now();
        $user->save();

        return $user;
    }
}
