<?php

namespace App\Actions\Proyectos;

// Imports: modelos de catálogo, colecciones de Eloquent y utilidades de texto (Str).
use App\Models\Docente;
use App\Models\Materia;
use App\Models\Tecnologia;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Traduce los nombres que escribe una persona (o Claude por MCP) a los ids del catálogo.
 * Ej.: "fabian suarez" → docente "Fabián Enrique Suárez Carvajal".
 *
 * Los catálogos son pequeños (decenas de filas), así que se comparan en PHP sin
 * acentos ni mayúsculas en vez de depender de la extensión `unaccent` de PostgreSQL.
 */
class ResolverCatalogos
{
    /** Docente cuyo nombre contiene todas las palabras escritas. null si no hay uno solo. */
    public function docente(string $nombre): ?Docente
    {
        return $this->unico(Docente::query()->orderBy('nombre')->get(), $nombre, fn (Docente $d): array => [$d->nombre]);
    }

    /** Materia por código exacto (`22A14`) o por palabras de su nombre. */
    public function materia(string $texto): ?Materia
    {
        return $this->unico(Materia::query()->orderBy('nombre')->get(), $texto, fn (Materia $m): array => [$m->nombre, $m->codigo]);
    }

    /**
     * Tecnologías por nombre exacto (sin distinguir mayúsculas ni acentos).
     * Devuelve las encontradas y las que no existen en el catálogo, para avisarle a quien publica.
     *
     * @param  list<string>  $nombres
     * @return array{ids: list<int>, desconocidas: list<string>}
     */
    public function tecnologias(array $nombres): array
    {
        $catalogo = Tecnologia::query()->get()->keyBy(fn (Tecnologia $t): string => $this->normalizar($t->nombre));
        $ids = [];
        $desconocidas = [];

        foreach ($nombres as $nombre) {
            $tecnologia = $catalogo->get($this->normalizar($nombre));
            $tecnologia ? $ids[] = $tecnologia->id : $desconocidas[] = $nombre;
        }

        return ['ids' => array_values(array_unique($ids)), 'desconocidas' => $desconocidas];
    }

    /**
     * Busca primero una coincidencia exacta y luego una que contenga todas las palabras.
     * Si varias coinciden, devuelve null: es mejor pedir precisión que adivinar.
     *
     * @template T of Model
     *
     * @param  Collection<int, T>  $opciones
     * @param  \Closure(T): list<string>  $textos
     * @return T|null
     */
    private function unico(Collection $opciones, string $buscado, \Closure $textos): ?Model
    {
        $buscado = $this->normalizar($buscado);
        if ($buscado === '') {
            return null;
        }

        $exacta = $opciones->first(fn (Model $o): bool => in_array($buscado, array_map($this->normalizar(...), $textos($o)), true));
        if ($exacta) {
            return $exacta;
        }

        $palabras = explode(' ', $buscado);
        $candidatas = $opciones->filter(function (Model $o) use ($palabras, $textos): bool {
            $texto = implode(' ', array_map($this->normalizar(...), $textos($o)));

            return collect($palabras)->every(fn (string $p): bool => str_contains($texto, $p));
        });

        return $candidatas->count() === 1 ? $candidatas->first() : null;
    }

    /** "  Fabián  SUÁREZ " → "fabian suarez". */
    private function normalizar(string $texto): string
    {
        return (string) Str::of(Str::ascii($texto))->lower()->squish();
    }
}
