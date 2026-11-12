<?php

namespace Database\Seeders;

// Imports: modelo Tecnologia y clase Seeder.
use App\Models\Tecnologia;
use Illuminate\Database\Seeder;

/** Catálogo inicial de tecnologías que se pueden elegir al publicar. */
class TecnologiaSeeder extends Seeder
{
    public function run(): void
    {
        $tecnologias = [
            'PHP', 'Laravel', 'JavaScript', 'TypeScript', 'React', 'Vue', 'Angular', 'Node.js',
            'Express', 'Python', 'Django', 'Flask', 'Java', 'Spring Boot', 'C#', '.NET',
            'Kotlin', 'Flutter', 'Dart', 'PostgreSQL', 'MySQL', 'MongoDB', 'SQLite',
            'Docker', 'Azure', 'Git', 'Tailwind CSS',
        ];

        foreach ($tecnologias as $nombre) {
            Tecnologia::firstOrCreate(['nombre' => $nombre]);
        }
    }
}
