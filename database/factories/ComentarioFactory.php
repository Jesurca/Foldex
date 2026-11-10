<?php

namespace Database\Factories;

use App\Models\Comentario;
use App\Models\Proyecto;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
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
