<?php

namespace App\Actions\Auth;

// Imports: rol (enum) y modelo de usuario.
use App\Enums\Role;
use App\Models\User;

/** Registro público: siempre crea un estudiante, aunque el body pida otro rol. */
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
