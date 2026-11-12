<?php

namespace App\Http\Controllers\Api\V1;

// Imports: controlador base, Resources y modelos de los catálogos.
use App\Http\Controllers\Controller;
use App\Http\Resources\DocenteResource;
use App\Http\Resources\MateriaResource;
use App\Http\Resources\TecnologiaResource;
use App\Models\Docente;
use App\Models\Materia;
use App\Models\Tecnologia;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/** Catálogos públicos para filtros y para el formulario de publicar. Son pequeños: sin paginación. */
class CatalogoController extends Controller
{
    // Ruta: GET /api/v1/materias
    public function materias(): AnonymousResourceCollection
    {
        return MateriaResource::collection(Materia::orderBy('semestre')->orderBy('nombre')->get());
    }

    // Ruta: GET /api/v1/docentes
    public function docentes(): AnonymousResourceCollection
    {
        return DocenteResource::collection(Docente::orderBy('nombre')->get());
    }

    // Ruta: GET /api/v1/tecnologias
    public function tecnologias(): AnonymousResourceCollection
    {
        return TecnologiaResource::collection(Tecnologia::orderBy('nombre')->get());
    }
}
