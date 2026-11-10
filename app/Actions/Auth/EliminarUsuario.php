<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

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
