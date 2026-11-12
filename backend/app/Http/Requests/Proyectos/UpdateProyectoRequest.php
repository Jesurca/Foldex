<?php

namespace App\Http\Requests\Proyectos;

/**
 * Editar un proyecto (solo admin, lo decide `ProyectoPolicy::update`).
 * Mismas reglas que publicar; además `quitar_portada` borra la foto actual.
 * El slug no cambia aunque cambie el título, para no romper enlaces compartidos.
 */
class UpdateProyectoRequest extends StoreProyectoRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('proyecto'));
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'quitar_portada' => ['sometimes', 'boolean'],
        ];
    }
}
