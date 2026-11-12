<?php

namespace Database\Factories;

// Imports: modelo Docente y la clase base Factory.
use App\Models\Docente;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Genera docentes falsos para las pruebas.
 *
 * @extends Factory<Docente>
 */
class DocenteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nombre' => fake()->name(),
            'email' => Str::lower(fake()->unique()->userName()).'@'.config('auth.allowed_domain'),
        ];
    }
}
