<?php

namespace Database\Seeders;

use App\Models\Materia;
use Illuminate\Database\Seeder;

class MateriaSeeder extends Seeder
{
    /**
     * Materias de Ingeniería de Sistemas UNAB con componente de programación.
     * Los códigos son referenciales: validar contra el pensum oficial vigente.
     */
    public function run(): void
    {
        $materias = [
            ['ISI-101', 'Introducción a la Ingeniería de Sistemas', 1],
            ['ISI-102', 'Algoritmos y Lógica de Programación', 1],
            ['ISI-201', 'Programación Orientada a Objetos', 2],
            ['ISI-202', 'Matemáticas Discretas', 2],
            ['ISI-301', 'Estructuras de Datos', 3],
            ['ISI-302', 'Bases de Datos', 3],
            ['ISI-401', 'Análisis y Diseño de Software', 4],
            ['ISI-402', 'Programación Web', 4],
            ['ISI-501', 'Ingeniería de Software', 5],
            ['ISI-502', 'Sistemas Operativos', 5],
            ['ISI-601', 'Desarrollo Backend', 6],
            ['ISI-602', 'Desarrollo Frontend', 6],
            ['ISI-603', 'Redes de Computadores', 6],
            ['ISI-701', 'Desarrollo de Aplicaciones Móviles', 7],
            ['ISI-702', 'Inteligencia Artificial', 7],
            ['ISI-801', 'Computación en la Nube', 8],
            ['ISI-802', 'Seguridad Informática', 8],
            ['ISI-901', 'Proyecto de Grado I', 9],
            ['ISI-1001', 'Proyecto de Grado II', 10],
        ];

        foreach ($materias as [$codigo, $nombre, $semestre]) {
            Materia::updateOrCreate(['codigo' => $codigo], ['nombre' => $nombre, 'semestre' => $semestre]);
        }
    }
}
