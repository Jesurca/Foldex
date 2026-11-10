<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Support\Str;

/**
 * Normaliza el correo y centraliza la regla del dominio institucional
 * para registro, login y creación de admins.
 */
trait ValidaCorreoInstitucional
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => Str::lower(trim($this->input('email')))]);
        }
    }

    protected function reglaDominio(): string
    {
        return 'ends_with:@'.config('auth.allowed_domain');
    }

    /** @return array<string, string> */
    protected function mensajesDominio(): array
    {
        return [
            'email.ends_with' => 'Solo se permiten correos institucionales @'.config('auth.allowed_domain'),
        ];
    }
}
