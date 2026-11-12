<?php

namespace App\Http\Requests\Auth;

// Imports: FormRequest.
use Illuminate\Foundation\Http\FormRequest;

/** Validación de POST /api/v1/me/tokens (nombre del token, p. ej. "Claude (MCP)"). */
class StoreTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:100'],
        ];
    }
}
