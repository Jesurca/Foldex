<?php

namespace App\Actions\Auth;

// Imports: modelo de usuario, excepción 403 y transacciones de base de datos.
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

/** Elimina una cuenta y revoca sus tokens en una sola transacción. Un admin no puede borrarse a sí mismo. */
class EliminarUsuario
{
    public function __invoke(User $admin, User $user): void
    {
        if ($admin->is($user)) {
            throw new AuthorizationException('Un administrador no puede eliminar su propia cuenta.');
        }

        DB::transaction(function () use ($user): void {
            $user->tokens()->delete();
            $user->delete();
        });
    }
}
