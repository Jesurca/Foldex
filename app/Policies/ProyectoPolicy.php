<?php

namespace App\Policies;

use App\Models\Proyecto;
use App\Models\User;

/**
 * Quién puede hacer qué con un proyecto concreto. Laravel la asocia sola a `Proyecto`
 * por convención de nombres y se consulta con `Gate::authorize()` o `$user->can()`.
 *
 * - Estudiante: publica y borra solo sus propios proyectos.
 * - Admin: edita y borra cualquier proyecto.
 */
class ProyectoPolicy
{
    /** Cualquier usuario autenticado puede publicar. */
    public function create(User $user): bool
    {
        return true;
    }

    /** Editar es moderación: solo el admin (el estudiante borra y vuelve a publicar). */
    public function update(User $user, Proyecto $proyecto): bool
    {
        return $user->esAdmin();
    }

    /** Borrar: el dueño o un admin. */
    public function delete(User $user, Proyecto $proyecto): bool
    {
        return $user->esAdmin() || $user->id === $proyecto->user_id;
    }
}
