<?php

namespace Database\Seeders;

use App\Models\Docente;
use Illuminate\Database\Seeder;

class DocenteSeeder extends Seeder
{
    /**
     * Catálogo inicial. Solo el docente del curso es real; el resto son marcadores
     * que el comité debe reemplazar con el listado oficial (vía /admin/docentes).
     */
    public function run(): void
    {
        $docentes = [
            'Fabián Enrique Suárez Carvajal',
            'Docente de Bases de Datos (por definir)',
            'Docente de Programación (por definir)',
            'Docente de Ingeniería de Software (por definir)',
        ];

        foreach ($docentes as $nombre) {
            Docente::firstOrCreate(['nombre' => $nombre]);
        }
    }
}
