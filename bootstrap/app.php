<?php

// Imports: middleware propios, excepciones que se traducen a JSON en español y clases de arranque de Laravel.
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
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/*
| Arranque de Laravel. El código está separado en dos carpetas del mismo proyecto:
|   backend/  → app, config, database, lang y routes (Laravel)
|   frontend/ → js, css, views y contexto_img (React + Vite)
| Por eso aquí se le indica a Laravel dónde está cada cosa (ver el final del archivo).
*/
$app = Application::configure(basePath: dirname(__DIR__))
    // Rutas: web (SPA), api (/api/v1), consola y MCP (/mcp/proyectos, en backend/routes/ai.php).
    ->withRouting(
        web: __DIR__.'/../backend/routes/web.php',
        api: __DIR__.'/../backend/routes/api.php',
        commands: __DIR__.'/../backend/routes/console.php',
        health: '/up',
        then: fn () => Route::group([], __DIR__.'/../backend/routes/ai.php'),
    )
    // Middleware: JSON forzado en /api, alias `role` (EnsureRole) y sin redirección a /login en la API.
    ->withMiddleware(function (Middleware $middleware): void {
        // En Azure App Service el HTTPS termina en el balanceador: se confía en sus cabeceras X-Forwarded-*
        // para que Laravel sepa que la petición es https y genere bien las URLs (assets de Vite, portadas).
        $middleware->trustProxies(at: '*');
        $middleware->api(prepend: [ForzarRespuestaJson::class]);
        $middleware->alias(['role' => EnsureRole::class]);
        $middleware->redirectGuestsTo(fn (Request $request): ?string => $request->is('api/*') ? null : '/');
    })
    // Errores: en /api y /mcp siempre JSON con mensajes en español (401, 403, 404, 405, 429).
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

// Carpetas del backend fuera de la raíz (composer.json apunta App\ y Database\ a las mismas rutas).
$app->useAppPath($app->basePath('backend/app'));
$app->useConfigPath($app->basePath('backend/config'));
$app->useDatabasePath($app->basePath('backend/database'));
$app->useLangPath($app->basePath('backend/lang'));

return $app;
