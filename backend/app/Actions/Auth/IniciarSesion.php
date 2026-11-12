<?php

namespace App\Actions\Auth;

// Imports: usuario, verificación de contraseñas (Hash) y error 422 de validación.
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/** Verifica correo y contraseña. Responde el mismo error si falla cualquiera de los dos (no revela qué cuentas existen). */
class IniciarSesion
{
    /**
     * Devuelve el usuario si las credenciales son válidas. El mensaje es el mismo
     * si el correo no existe o la contraseña falla, para no revelar cuentas.
     */
    public function __invoke(string $email, string $password): User
    {
        $user = User::where('email', $email)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        return $user;
    }
}
