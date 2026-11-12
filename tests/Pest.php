<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/** PNG real (gris) en bytes, generado sin GD (no está instalado); así `image` y `dimensions` lo leen de verdad. */
function pngBytes(int $ancho, int $alto): string
{
    $bloque = fn (string $tipo, string $datos) => pack('N', strlen($datos)).$tipo.$datos.pack('N', crc32($tipo.$datos));
    $filas = str_repeat("\0".str_repeat("\x80", $ancho), $alto);

    return "\x89PNG\r\n\x1a\n"
        .$bloque('IHDR', pack('NNCCCCC', $ancho, $alto, 8, 0, 0, 0, 0))
        .$bloque('IDAT', gzcompress($filas))
        .$bloque('IEND', '');
}

/** El mismo PNG como archivo subido desde un formulario. */
function pngDePrueba(string $nombre, int $ancho, int $alto): UploadedFile
{
    return UploadedFile::fake()->createWithContent($nombre, pngBytes($ancho, $alto));
}
