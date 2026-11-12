<?php

// Pruebas del carrusel: la lista se lee en vivo de la carpeta y solo se sirven imágenes de ella.

use Illuminate\Support\Facades\File;

beforeEach(function (): void {
    $this->carpeta = storage_path('framework/testing/carrusel');
    File::deleteDirectory($this->carpeta);
    File::ensureDirectoryExists($this->carpeta);
    config(['carrusel.ruta' => $this->carpeta]);
});

afterEach(fn () => File::deleteDirectory($this->carpeta));

it('lista solo las imágenes, ordenadas por nombre', function (): void {
    File::put("{$this->carpeta}/b-foto.jpg", pngBytes(10, 10));
    File::put("{$this->carpeta}/a-foto.png", pngBytes(10, 10));
    File::put("{$this->carpeta}/notas.txt", 'no soy imagen');
    File::put("{$this->carpeta}/logo.svg", '<svg/>');

    $this->getJson('/api/v1/carrusel')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.nombre', 'a-foto.png')
        ->assertJsonPath('data.1.nombre', 'b-foto.jpg');
});

it('una foto nueva aparece sin recompilar nada', function (): void {
    File::put("{$this->carpeta}/1.jpg", pngBytes(10, 10));
    $this->getJson('/api/v1/carrusel')->assertJsonCount(1, 'data');

    File::put("{$this->carpeta}/2.jpg", pngBytes(10, 10));
    $this->getJson('/api/v1/carrusel')->assertJsonCount(2, 'data');
});

it('sirve la imagen de la lista', function (): void {
    File::put("{$this->carpeta}/foto 1.png", pngBytes(10, 10));

    $url = $this->getJson('/api/v1/carrusel')->json('data.0.url');

    $this->get($url)->assertOk()->assertHeader('Content-Type', 'image/png');
});

it('no sirve archivos fuera de la lista', function (string $archivo): void {
    File::put("{$this->carpeta}/notas.txt", 'secreto');

    $this->get('/api/v1/carrusel/'.$archivo)->assertNotFound();
})->with(['notas.txt', 'no-existe.jpg', '..%2F..%2F.env']);

it('devuelve una lista vacía si la carpeta no existe', function (): void {
    config(['carrusel.ruta' => $this->carpeta.'/no-existe']);

    $this->getJson('/api/v1/carrusel')->assertOk()->assertJsonCount(0, 'data');
});
