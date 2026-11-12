<?php

// Comandos de consola propios (`php artisan ...`). Por ahora solo el de ejemplo de Laravel.

// Imports: frases de ejemplo y la fachada de Artisan.
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

// php artisan inspire → muestra una frase.
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
