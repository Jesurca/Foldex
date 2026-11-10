<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\Concerns\ValidaCorreoInstitucional;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
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
            'name' => ['required', 'string', 'min:3', 'max:150'],
            'email' => ['required', 'string', 'email', 'max:255', $this->reglaDominio(), 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)->letters()->numbers()],
            'programa' => ['nullable', 'string', 'max:150'],
            'semestre' => ['nullable', 'integer', 'between:1,10'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return $this->mensajesDominio();
    }
}
