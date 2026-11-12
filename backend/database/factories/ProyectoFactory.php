<?php

namespace Database\Factories;

// Imports: modelos relacionados y la clase base Factory.
use App\Models\Docente;
use App\Models\Materia;
use App\Models\Proyecto;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Genera proyectos falsos para las pruebas (crea también autor, materia y docente).
 *
 * @extends Factory<Proyecto>
 */
class ProyectoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'materia_id' => Materia::factory(),
            'docente_id' => Docente::factory(),
            'titulo' => ucfirst(fake()->words(4, true)),
            'contexto' => fake()->paragraphs(2, true),
            'repositorio_url' => 'https://github.com/unab/'.fake()->unique()->slug(3),
            'video_url' => 'https://www.youtube.com/watch?v='.fake()->regexify('[A-Za-z0-9]{11}'),
            'periodo' => fake()->numberBetween(2023, 2026).'-'.fake()->randomElement([1, 2]),
        ];
    }
}
