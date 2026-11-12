<?php

namespace Database\Factories;

// Imports: modelo Tecnologia y la clase base Factory.
use App\Models\Tecnologia;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Genera tecnologías falsas para las pruebas.
 *
 * @extends Factory<Tecnologia>
 */
class TecnologiaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nombre' => fake()->unique()->bothify('Tecnologia ####'),
        ];
    }
}
