<?php

namespace App\Http\Controllers\Api\V1\Admin;

// Imports: Actions de usuarios, validación (FormRequest), Resource y tipos de respuesta HTTP.
use App\Actions\Auth\CrearAdmin;
use App\Actions\Auth\EliminarUsuario;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAdminUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/** Gestión de usuarios por el admin (grupo /api/v1/admin con middleware role:admin). */
class UserController extends Controller
{
    // Ruta: GET /api/v1/admin/users?q=&role=
    public function index(Request $request): AnonymousResourceCollection
    {
        $usuarios = User::query()
            ->when($request->string('q')->trim()->value(), fn ($q, string $texto) => $q->where(
                fn ($w) => $w->where('name', 'ilike', "%{$texto}%")->orWhere('email', 'ilike', "%{$texto}%")
            ))
            ->when($request->query('role'), fn ($q, string $rol) => $q->where('role', $rol))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return UserResource::collection($usuarios);
    }

    // Ruta: POST /api/v1/admin/users
    public function store(StoreAdminUserRequest $request, CrearAdmin $crear): JsonResponse
    {
        return UserResource::make($crear($request->validated())->refresh())
            ->response()
            ->setStatusCode(201);
    }

    // Ruta: DELETE /api/v1/admin/users/{user}
    public function destroy(Request $request, User $user, EliminarUsuario $eliminar): Response
    {
        $eliminar($request->user(), $user);

        return response()->noContent();
    }
}
