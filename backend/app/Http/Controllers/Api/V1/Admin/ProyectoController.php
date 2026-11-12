<?php

namespace App\Http\Controllers\Api\V1\Admin;

// Imports: controlador base, Resource de proyecto, modelo y tipos de respuesta HTTP.
use App\Http\Controllers\Controller;
use App\Http\Resources\ProyectoResource;
use App\Models\Proyecto;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/** Moderación de publicaciones. El acceso lo controla `role:admin` en el grupo de rutas. */
class ProyectoController extends Controller
{
    // Ruta: GET /api/v1/admin/proyectos?q=
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

    // Ruta: DELETE /api/v1/admin/proyectos/{slug}
    /** Borrado lógico (softDeletes): queda evidencia para auditoría. */
    public function destroy(Proyecto $proyecto): Response
    {
        $proyecto->delete();

        return response()->noContent();
    }
}
