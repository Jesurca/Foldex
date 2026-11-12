<?php

namespace Database\Factories;

// Imports: modelo Materia y la clase base Factory.
use App\Models\Materia;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Genera materias falsas para las pruebas.
 *
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
