<?php

namespace App\Http\Requests\Proyectos;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Publicar un proyecto. El autor (`user_id`), el `slug` y la fecha los asigna el
 * servidor; si el body los trae, se ignoran porque no están en las reglas.
 */
class StoreProyectoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        foreach (['titulo', 'contexto', 'repositorio_url', 'video_url', 'periodo'] as $campo) {
            if (is_string($this->input($campo))) {
                $this->merge([$campo => trim($this->input($campo))]);
            }
        }

        if ($this->input('video_url') === '') {
            $this->merge(['video_url' => null]);
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $anioActual = (int) now()->format('Y');

        return [
            'titulo' => ['required', 'string', 'min:3', 'max:150'],
            'contexto' => ['required', 'string', 'min:20', 'max:5000'],
            'materia_id' => ['required', 'integer', 'exists:materias,id'],
            'docente_id' => ['required', 'integer', 'exists:docentes,id'],
            'periodo' => ['required', 'string', 'regex:/^\d{4}-[12]$/', function (string $atributo, string $valor, \Closure $fallo) use ($anioActual): void {
                $inicio = (int) substr($valor, 0, 4);
                if ($inicio < 2000 || $inicio > $anioActual) {
                    $fallo("El periodo debe estar entre 2000 y {$anioActual}.");
                }
            }],
            'repositorio_url' => ['required', 'string', 'url:https', 'max:500'],
            'video_url' => ['nullable', 'string', 'url:https', 'max:500'],
            'tecnologias' => ['required', 'array', 'min:1', 'max:10'],
            'tecnologias.*' => ['integer', 'distinct', 'exists:tecnologias,id'],
            // 2 MB: el `upload_max_filesize` por defecto de PHP. `image` ya excluye SVG (puede llevar scripts).
            'portada' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:min_width=300,min_height=150'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'periodo.regex' => 'El periodo debe tener el formato AAAA-1 o AAAA-2 (por ejemplo 2026-2).',
            'repositorio_url.url' => 'El repositorio debe ser un enlace https:// válido.',
            'video_url.url' => 'El video debe ser un enlace https:// válido.',
            'tecnologias.required' => 'Selecciona al menos una tecnología.',
            'tecnologias.min' => 'Selecciona al menos una tecnología.',
            'tecnologias.max' => 'Selecciona como máximo 10 tecnologías.',
            'portada.image' => 'La portada debe ser una imagen JPG, PNG o WebP.',
            'portada.mimes' => 'La portada debe ser una imagen JPG, PNG o WebP.',
            'portada.max' => 'La portada no puede pesar más de 2 MB.',
            'portada.uploaded' => 'La portada no se pudo subir. Debe pesar menos de 2 MB.',
            'portada.dimensions' => 'La portada debe medir al menos 300 × 150 píxeles.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'titulo' => 'nombre del proyecto',
            'contexto' => 'contexto',
            'materia_id' => 'materia',
            'docente_id' => 'docente',
            'repositorio_url' => 'repositorio',
            'video_url' => 'video',
            'portada' => 'foto de portada',
        ];
    }
}
