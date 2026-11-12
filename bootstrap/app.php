<?php

use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\ForzarRespuestaJson;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [ForzarRespuestaJson::class]);
        $middleware->alias(['role' => EnsureRole::class]);
        $middleware->redirectGuestsTo(fn (Request $request): ?string => $request->is('api/*') ? null : '/');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $esApi = fn (Request $request): bool => $request->is('api/*', 'mcp/*');

        $exceptions->render(fn (AuthenticationException $e, Request $request) => $esApi($request)
            ? response()->json(['message' => 'No autenticado. Inicia sesión y envía el token Bearer.'], 401)
            : null);

        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) use ($esApi) {
            if (! $esApi($request)) {
                return null;
            }
            $previa = $e->getPrevious();
            $mensaje = $previa instanceof AuthorizationException && $previa->getMessage() !== 'This action is unauthorized.'
                ? $previa->getMessage()
                : 'No tienes permiso para realizar esta acción.';

            return response()->json(['message' => $mensaje], 403);
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) use ($esApi) {
            if (! $esApi($request)) {
                return null;
            }
            $mensaje = $e->getPrevious() instanceof ModelNotFoundException
                ? 'El recurso solicitado no existe.'
                : 'Ruta no encontrada.';

            return response()->json(['message' => $mensaje], 404);
        });

        $exceptions->render(fn (MethodNotAllowedHttpException $e, Request $request) => $esApi($request)
            ? response()->json(['message' => 'Método HTTP no permitido para esta ruta.'], 405)
            : null);

        $exceptions->render(fn (ThrottleRequestsException $e, Request $request) => $esApi($request)
            ? response()->json(['message' => 'Demasiadas solicitudes. Intenta de nuevo más tarde.'], 429, $e->getHeaders())
            : null);
    })->create();
