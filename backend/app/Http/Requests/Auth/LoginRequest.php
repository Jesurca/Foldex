<?php

namespace App\Http\Requests\Auth;

// Imports: regla del correo institucional y FormRequest.
use App\Http\Requests\Concerns\ValidaCorreoInstitucional;
use Illuminate\Foundation\Http\FormRequest;

/** Validación de POST /api/v1/login. */
class LoginRequest extends FormRequest
{
    use ValidaCorreoInstitucional;

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255', $this->reglaDominio()],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return $this->mensajesDominio();
    }
}
