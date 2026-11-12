<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProyectoResource;
use App\Models\Proyecto;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class MiEspacioController extends Controller
{
    public function proyectos(Request $request): AnonymousResourceCollection
    {
        $proyectos = Proyecto::query()
            ->conDetalles($request->user())
            ->whereBelongsTo($request->user())
            ->latest()
            ->orderByDesc('id')
            ->paginate(12);

        return ProyectoResource::collection($proyectos);
    }

    /** Proyectos a los que el usuario dio like, del like más reciente al más antiguo. */
    public function likes(Request $request): AnonymousResourceCollection
    {
        $usuario = $request->user();

        $proyectos = Proyecto::query()
            ->conDetalles($usuario)
            ->whereHas('likes', fn (Builder $q) => $q->where('users.id', $usuario->id))
            ->orderByDesc(DB::table('likes')->select('created_at')
                ->whereColumn('likes.proyecto_id', 'proyectos.id')
                ->where('likes.user_id', $usuario->id))
            ->orderByDesc('proyectos.id')
            ->paginate(12);

        return ProyectoResource::collection($proyectos);
    }
}
