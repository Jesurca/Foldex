<?php

namespace App\Http\Requests\Proyectos;

use Illuminate\Foundation\Http\FormRequest;

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
