<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Auth\EmitirToken;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\StoreTokenRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laravel\Sanctum\PersonalAccessToken;

/** Tokens personales (p. ej. para conectar el servidor MCP desde un cliente de IA). */
class TokenController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tokens = $request->user()->tokens()->latest()->get()
            ->map(fn (PersonalAccessToken $token): array => [
                'id' => $token->id,
                'nombre' => $token->name,
                'ultimo_uso' => $token->last_used_at?->toIso8601String(),
                'creado_en' => $token->created_at?->toIso8601String(),
                'actual' => $token->is($request->user()->currentAccessToken()),
            ]);

        return response()->json(['data' => $tokens]);
    }

    public function store(StoreTokenRequest $request, EmitirToken $emitir): JsonResponse
    {
        return response()->json([
            'token' => $emitir($request->user(), $request->validated('nombre')),
            'aviso' => 'Guarda este token: no se volverá a mostrar.',
        ], 201);
    }

    public function destroy(Request $request, int $token): Response
    {
        $request->user()->tokens()->whereKey($token)->firstOrFail()->delete();

        return response()->noContent();
    }
}
