<?php

namespace App\Mcp\Tools;

// Imports: modelos de catálogo y clases base de laravel/mcp.
use App\Models\Docente;
use App\Models\Materia;
use App\Models\Tecnologia;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

/** Le muestra a Claude quién está conectado y los valores válidos de cada catálogo. */
#[Name('listar_catalogos')]
#[Description('Devuelve la cuenta conectada (el publicante) y los catálogos válidos: materias, docentes y tecnologías. Llámala antes de publicar.')]
#[IsReadOnly]
class ListarCatalogos extends Tool
{
    public function handle(Request $request): Response
    {
        $usuario = $request->user();

        return Response::json([
            'publicante' => ['nombre' => $usuario?->name, 'correo' => $usuario?->email],
            'materias' => Materia::query()->orderBy('semestre')->orderBy('nombre')->get(['codigo', 'nombre', 'semestre']),
            'docentes' => Docente::query()->orderBy('nombre')->pluck('nombre'),
            'tecnologias' => Tecnologia::query()->orderBy('nombre')->pluck('nombre'),
        ]);
    }
}
