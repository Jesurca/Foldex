<?php

namespace App\Http\Controllers\Api\V1\Admin;

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

class UserController extends Controller
{
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

    public function store(StoreAdminUserRequest $request, CrearAdmin $crear): JsonResponse
    {
        return UserResource::make($crear($request->validated())->refresh())
            ->response()
            ->setStatusCode(201);
    }

    public function destroy(Request $request, User $user, EliminarUsuario $eliminar): Response
    {
        $eliminar($request->user(), $user);

        return response()->noContent();
    }
}
