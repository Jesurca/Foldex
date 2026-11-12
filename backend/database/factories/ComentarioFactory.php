<?php

namespace Database\Factories;

// Imports: modelos relacionados y la clase base Factory.
use App\Models\Comentario;
use App\Models\Proyecto;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Genera comentarios falsos para las pruebas (con su proyecto y autor).
 *
 * @extends Factory<Comentario>
 */
class ComentarioFactory extends Factory
{
    public function definition(): array
    {
        return [
            'proyecto_id' => Proyecto::factory(),
            'user_id' => User::factory(),
            'contenido' => fake()->sentence(12),
        ];
    }
}
