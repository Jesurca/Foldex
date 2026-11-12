<?php

namespace App\Http\Controllers\Api\V1;

// Imports: validación del comentario, Resource, modelos, tipos de respuesta y Gate (Policies).
use App\Http\Controllers\Controller;
use App\Http\Requests\Proyectos\StoreComentarioRequest;
use App\Http\Resources\ComentarioResource;
use App\Models\Comentario;
use App\Models\Proyecto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

/** Comentarios de un proyecto: listar (público), crear (con sesión) y borrar (autor o admin). */
class ComentarioController extends Controller
{
    // Ruta: GET /api/v1/proyectos/{slug}/comentarios
    public function index(Proyecto $proyecto): AnonymousResourceCollection
    {
        return ComentarioResource::collection(
            $proyecto->comentarios()->with('user')->latest()->orderByDesc('id')->paginate(10)
        );
    }

    // Ruta: POST /api/v1/proyectos/{slug}/comentarios
    public function store(StoreComentarioRequest $request, Proyecto $proyecto): JsonResponse
    {
        $comentario = new Comentario($request->validated());
        $comentario->user()->associate($request->user());
        $comentario->proyecto()->associate($proyecto);
        $comentario->save();

        return ComentarioResource::make($comentario->load('user'))->response()->setStatusCode(201);
    }

    // Ruta: DELETE /api/v1/comentarios/{id}
    public function destroy(Comentario $comentario): Response
    {
        Gate::authorize('delete', $comentario);
        $comentario->delete();

        return response()->noContent();
    }
}
