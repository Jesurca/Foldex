<?php

namespace App\Actions\Auth;

use App\Enums\Role;
use App\Models\User;

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
