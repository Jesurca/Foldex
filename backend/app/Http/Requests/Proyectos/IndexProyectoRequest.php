<?php

namespace App\Http\Requests\Proyectos;

// Imports: FormRequest.
use Illuminate\Foundation\Http\FormRequest;

/** Filtros del catálogo (query string). Todo es opcional. */
class IndexProyectoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'materia' => ['nullable', 'integer'],
            'docente' => ['nullable', 'integer'],
            'tecnologia' => ['nullable', 'integer'],
            'semestre' => ['nullable', 'integer', 'between:1,10'],
            'periodo' => ['nullable', 'string', 'regex:/^\d{4}-[12]$/'],
            'orden' => ['nullable', 'in:recientes,populares'],
            'rango' => ['nullable', 'in:diario,semanal,mensual,anual,historico'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
