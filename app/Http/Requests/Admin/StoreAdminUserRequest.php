<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Concerns\ValidaCorreoInstitucional;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreAdminUserRequest extends FormRequest
{
    use ValidaCorreoInstitucional;

    /** El acceso real lo controla el middleware `role:admin` del grupo. */
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
            'password' => ['required', 'string', Password::min(10)->letters()->numbers()->symbols()],
            'programa' => ['nullable', 'string', 'max:150'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return $this->mensajesDominio();
    }
}
