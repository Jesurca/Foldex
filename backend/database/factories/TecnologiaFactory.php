<?php

namespace Database\Factories;

use App\Models\Tecnologia;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
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
