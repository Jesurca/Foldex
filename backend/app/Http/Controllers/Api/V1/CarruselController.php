<?php

namespace App\Http\Controllers\Api\V1;

// Imports: controlador base, respuestas JSON y de archivo.
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Fotos del carrusel de la portada, leídas en vivo de la carpeta `config('carrusel.ruta')`
 * (frontend/contexto_img). Una foto nueva aparece en la página sin recompilar; la URL lleva
 * la fecha de modificación (`?v=`) para que el navegador no muestre una versión vieja.
 */
class CarruselController extends Controller
{
    // Ruta: GET /api/v1/carrusel
    public function index(): JsonResponse
    {
        $fotos = collect($this->archivos())
            ->map(fn (string $ruta): array => [
                'nombre' => basename($ruta),
                'url' => url('/api/v1/carrusel/'.rawurlencode(basename($ruta))).'?v='.filemtime($ruta),
            ])
            ->values();

        return response()->json(['data' => $fotos]);
    }

    // Ruta: GET /api/v1/carrusel/{archivo}
    public function show(string $archivo): BinaryFileResponse
    {
        // Solo se sirve un archivo que esté en la lista: así nadie puede pedir `../../.env`.
        $ruta = collect($this->archivos())->first(fn (string $r): bool => basename($r) === $archivo);
        abort_unless($ruta, 404);

        return response()->file($ruta, ['Cache-Control' => 'public, max-age=86400']);
    }

    /** Imágenes de la carpeta, ordenadas por nombre (el orden del carrusel). @return list<string> */
    private function archivos(): array
    {
        $carpeta = (string) config('carrusel.ruta');
        if (! is_dir($carpeta)) {
            return [];
        }

        $extensiones = config('carrusel.extensiones');
        $archivos = array_filter(
            glob($carpeta.DIRECTORY_SEPARATOR.'*') ?: [],
            fn (string $r): bool => is_file($r) && in_array(strtolower(pathinfo($r, PATHINFO_EXTENSION)), $extensiones, true),
        );
        natcasesort($archivos);

        return array_values($archivos);
    }
}
