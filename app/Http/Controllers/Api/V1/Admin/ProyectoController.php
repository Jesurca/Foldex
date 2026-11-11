<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProyectoResource;
use App\Models\Proyecto;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/** Moderación de publicaciones. El acceso lo controla `role:admin` en el grupo de rutas. */
class ProyectoController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $request->validate(['q' => ['nullable', 'string', 'max:100']]);

        $proyectos = Proyecto::query()
            ->conDetalles($request->user())
            ->filtrar(['q' => $request->query('q')])
            ->paginate(15)
            ->withQueryString();

        return ProyectoResource::collection($proyectos);
    }

    /** Borrado lógico (softDeletes): queda evidencia para auditoría. */
    public function destroy(Proyecto $proyecto): Response
    {
        $proyecto->delete();

        return response()->noContent();
    }
}
