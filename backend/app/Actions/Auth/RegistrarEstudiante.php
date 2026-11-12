<?php

namespace App\Actions\Auth;

use App\Enums\Role;
use App\Models\User;

class RegistrarEstudiante
{
    /**
     * El registro público siempre crea estudiantes, aunque el body traiga `role`.
     *
     * @param  array{name: string, email: string, password: string, programa?: ?string, semestre?: ?int}  $datos
     */
    public function __invoke(array $datos): User
    {
        $user = new User($datos);
        $user->role = Role::Estudiante;
        $user->save();

        return $user;
    }
}
