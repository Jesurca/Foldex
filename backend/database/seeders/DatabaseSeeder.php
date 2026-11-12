<?php

namespace Database\Seeders;

// Imports: desactivar eventos de modelo al sembrar y la clase base Seeder.
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/** Punto de entrada de `php artisan db:seed`: catálogos, admin y (en local) datos de demostración. */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            MateriaSeeder::class,
            DocenteSeeder::class,
            TecnologiaSeeder::class,
            AdminSeeder::class,
        ]);

        if (app()->isLocal()) {
            $this->call(DemoSeeder::class);
        }
    }
}
