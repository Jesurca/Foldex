<?php

namespace App\Http\Requests\Proyectos;

// Imports: FormRequest.
use Illuminate\Foundation\Http\FormRequest;

/** Validación de POST /api/v1/proyectos/{slug}/comentarios. */
class StoreComentarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('contenido'))) {
            $this->merge(['contenido' => trim($this->input('contenido'))]);
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'contenido' => ['required', 'string', 'min:2', 'max:1000'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['contenido' => 'comentario'];
    }
}
