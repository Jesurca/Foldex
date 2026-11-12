<?php

namespace Database\Factories;

use App\Models\Materia;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Materia>
 */
class MateriaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'codigo' => fake()->unique()->bothify('ISX-####'),
            'nombre' => ucfirst(fake()->words(3, true)),
            'semestre' => fake()->numberBetween(1, 10),
        ];
    }
}
