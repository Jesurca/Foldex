<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Proyectos\StoreComentarioRequest;
use App\Http\Resources\ComentarioResource;
use App\Models\Comentario;
use App\Models\Proyecto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class ComentarioController extends Controller
{
    public function index(Proyecto $proyecto): AnonymousResourceCollection
    {
        return ComentarioResource::collection(
            $proyecto->comentarios()->with('user')->latest()->orderByDesc('id')->paginate(10)
        );
    }

    public function store(StoreComentarioRequest $request, Proyecto $proyecto): JsonResponse
    {
        $comentario = new Comentario($request->validated());
        $comentario->user()->associate($request->user());
        $comentario->proyecto()->associate($proyecto);
        $comentario->save();

        return ComentarioResource::make($comentario->load('user'))->response()->setStatusCode(201);
    }

    public function destroy(Comentario $comentario): Response
    {
        Gate::authorize('delete', $comentario);
        $comentario->delete();

        return response()->noContent();
    }
}
