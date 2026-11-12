<?php

namespace App\Http\Controllers\Api\V1;

// Imports: Actions de autenticación, validaciones de login/registro, Resource del usuario y tipos de respuesta HTTP.
use App\Actions\Auth\EmitirToken;
use App\Actions\Auth\IniciarSesion;
use App\Actions\Auth\RegistrarEstudiante;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/** Registro, inicio y cierre de sesión con tokens de Sanctum. */
class AuthController extends Controller
{
    // Ruta: POST /api/v1/register
    public function register(RegisterRequest $request, RegistrarEstudiante $registrar, EmitirToken $emitir): JsonResponse
    {
        $user = $registrar($request->safe()->except('device_name'));

        return response()->json([
            'token' => $emitir($user, $request->validated('device_name')),
            'user' => UserResource::make($user->refresh()),
        ], 201);
    }

    // Ruta: POST /api/v1/login
    public function login(LoginRequest $request, IniciarSesion $iniciar, EmitirToken $emitir): JsonResponse
    {
        $user = $iniciar($request->validated('email'), $request->validated('password'));

        return response()->json([
            'token' => $emitir($user, $request->validated('device_name')),
            'user' => UserResource::make($user),
        ]);
    }

    // Ruta: POST /api/v1/logout
    public function logout(Request $request): Response
    {
        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }

    // Ruta: GET /api/v1/me
    public function me(Request $request): UserResource
    {
        return UserResource::make($request->user());
    }
}
