<?php

namespace App\Http\Controllers\Api\V1;

// Imports: controlador base, modelo Proyecto, tipos HTTP y consultas directas a la tabla likes.
use App\Http\Controllers\Controller;
use App\Models\Proyecto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * POST da like y DELETE lo quita. Ambos son idempotentes (repetirlos no suma ni resta de más)
 * gracias a la PK compuesta `(user_id, proyecto_id)` de la tabla `likes`.
 */
class LikeController extends Controller
{
    // Ruta: POST /api/v1/proyectos/{slug}/like
    public function store(Request $request, Proyecto $proyecto): JsonResponse
    {
        // La hora del like la pone Laravel (UTC, la misma zona que usa el ranking) y no la sesión de
        // PostgreSQL. `insertOrIgnore` + PK compuesta: repetir el like no lo duplica ni cambia su hora.
        DB::table('likes')->insertOrIgnore([
            'user_id' => $request->user()->id,
            'proyecto_id' => $proyecto->id,
            'created_at' => now(),
        ]);

        return $this->estado($proyecto, true);
    }

    // Ruta: DELETE /api/v1/proyectos/{slug}/like
    public function destroy(Request $request, Proyecto $proyecto): JsonResponse
    {
        $proyecto->likes()->detach($request->user()->id);

        return $this->estado($proyecto, false);
    }

    private function estado(Proyecto $proyecto, bool $liked): JsonResponse
    {
        return response()->json([
            'liked' => $liked,
            'likes_count' => $proyecto->likes()->count(),
        ]);
    }
}
