<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Proyectos\PublicarProyecto;
use App\Http\Controllers\Controller;
use App\Http\Requests\Proyectos\IndexProyectoRequest;
use App\Http\Requests\Proyectos\StoreProyectoRequest;
use App\Http\Resources\ProyectoResource;
use App\Models\Proyecto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class ProyectoController extends Controller
{
    public function index(IndexProyectoRequest $request): AnonymousResourceCollection
    {
        $proyectos = Proyecto::query()
            ->conDetalles($request->user('sanctum'))
            ->filtrar($request->validated())
            ->paginate(12)
            ->withQueryString();

        return ProyectoResource::collection($proyectos);
    }

    public function show(Request $request, Proyecto $proyecto): ProyectoResource
    {
        return ProyectoResource::make(
            Proyecto::query()->conDetalles($request->user('sanctum'))->findOrFail($proyecto->id)
        );
    }

    public function store(StoreProyectoRequest $request, PublicarProyecto $publicar): JsonResponse
    {
        $proyecto = $publicar($request->user(), $request->validated());

        return ProyectoResource::make(Proyecto::query()->conDetalles($request->user())->findOrFail($proyecto->id))
            ->response()
            ->setStatusCode(201);
    }

    public function destroy(Proyecto $proyecto): Response
    {
        Gate::authorize('delete', $proyecto);
        $proyecto->delete();

        return response()->noContent();
    }
}
