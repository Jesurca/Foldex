<?php

namespace App\Http\Middleware;

// Imports: rol (enum), excepciones 401/403 y tipos de petición/respuesta.
use App\Enums\Role;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Uso: `->middleware('role:admin')` o `role:admin,estudiante`. */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            throw new AuthenticationException;
        }

        $permitidos = array_map(fn (string $rol): Role => Role::from($rol), $roles);

        if (! in_array($user->role, $permitidos, true)) {
            throw new AuthorizationException('No tienes permiso para realizar esta acción.');
        }

        return $next($request);
    }
}
