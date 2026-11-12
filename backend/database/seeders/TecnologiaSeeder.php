<?php

namespace Database\Seeders;

use App\Models\Tecnologia;
use Illuminate\Database\Seeder;

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
