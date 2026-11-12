<?php

namespace App\Actions\Proyectos;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Descarga la foto de portada que propone Claude por MCP (p. ej. la imagen social de GitHub)
 * y la convierte en un `UploadedFile`, para validarla y guardarla igual que una foto subida
 * desde el formulario web.
 *
 * Protecciones: solo `https`, el host debe resolver a una IP pública (evita que el servidor
 * consulte su red interna, SSRF), máximo 2 MB y 10 segundos.
 */
class DescargarPortada
{
    private const MAX_BYTES = 2 * 1024 * 1024;

    /** Devuelve null si la URL no es válida o la descarga falla: la portada es opcional. */
    public function __invoke(string $url): ?UploadedFile
    {
        if (! $this->esUrlPublica($url)) {
            return null;
        }

        try {
            $respuesta = Http::timeout(10)
                ->withOptions(['allow_redirects' => ['max' => 3, 'protocols' => ['https']]])
                ->withHeaders(['Accept' => 'image/jpeg,image/png,image/webp'])
                ->get($url);
        } catch (Throwable) {
            return null;
        }

        $contenido = $respuesta->body();
        if (! $respuesta->successful() || $contenido === '' || strlen($contenido) > self::MAX_BYTES) {
            return null;
        }

        $ruta = tempnam(sys_get_temp_dir(), 'portada_');
        if ($ruta === false) {
            return null;
        }
        file_put_contents($ruta, $contenido);

        // `test: true` porque el archivo no llegó por un formulario (is_uploaded_file sería false).
        // El tipo real lo detecta Laravel a partir del contenido al validar y al guardar.
        return new UploadedFile($ruta, 'portada', null, null, true);
    }

    private function esUrlPublica(string $url): bool
    {
        $partes = parse_url($url);
        if (($partes['scheme'] ?? null) !== 'https' || empty($partes['host'])) {
            return false;
        }

        $ip = gethostbyname($partes['host']);

        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
    }
}
