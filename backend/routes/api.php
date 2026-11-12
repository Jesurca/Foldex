<?php

// Rutas de la API REST. Todas llevan el prefijo /api (lo pone Laravel) y /v1 (abajo): /api/v1/...
// Middleware usados: auth:sanctum (token Bearer), role:admin (EnsureRole) y throttle (límite por minuto).

// Imports: los controladores de la API y la fachada de rutas.
use App\Http\Controllers\Api\V1\Admin\ProyectoController as AdminProyectoController;
use App\Http\Controllers\Api\V1\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CatalogoController;
use App\Http\Controllers\Api\V1\ComentarioController;
use App\Http\Controllers\Api\V1\LikeController;
use App\Http\Controllers\Api\V1\MiEspacioController;
use App\Http\Controllers\Api\V1\ProyectoController;
use App\Http\Controllers\Api\V1\TokenController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    // --- Salud: comprobar que la API responde ---
    Route::get('/ping', fn () => response()->json([
        'status' => 'ok',
        'app' => config('app.name'),
        'version' => 'v1',
    ]));

    // --- Autenticación pública (devuelve el token Bearer) ---
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:registro');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

    // Lectura pública: catálogos, proyectos y comentarios (si llega un token, se usa para `liked` y `can`).
    Route::get('/materias', [CatalogoController::class, 'materias']);
    Route::get('/docentes', [CatalogoController::class, 'docentes']);
    Route::get('/tecnologias', [CatalogoController::class, 'tecnologias']);
    Route::get('/proyectos', [ProyectoController::class, 'index']);
    Route::get('/proyectos/{proyecto}', [ProyectoController::class, 'show']);
    Route::get('/proyectos/{proyecto}/comentarios', [ComentarioController::class, 'index']);

    // --- Todo lo de aquí abajo exige token: Authorization: Bearer <token> ---
    Route::middleware('auth:sanctum')->group(function (): void {
        // Sesión
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);

        // Tokens personales (también los que usa Claude para conectarse por MCP)
        Route::get('/me/tokens', [TokenController::class, 'index']);
        Route::post('/me/tokens', [TokenController::class, 'store']);
        Route::delete('/me/tokens/{token}', [TokenController::class, 'destroy'])->whereNumber('token');

        // Mi espacio
        Route::get('/me/proyectos', [MiEspacioController::class, 'proyectos']);
        Route::get('/me/likes', [MiEspacioController::class, 'likes']);

        // Proyectos: publicar (cualquiera), editar (solo admin) y borrar (dueño o admin). Lo decide ProyectoPolicy.
        Route::post('/proyectos', [ProyectoController::class, 'store'])->middleware('throttle:10,1');
        Route::put('/proyectos/{proyecto}', [ProyectoController::class, 'update'])->middleware('throttle:30,1');
        Route::delete('/proyectos/{proyecto}', [ProyectoController::class, 'destroy']);

        // Me gusta
        Route::post('/proyectos/{proyecto}/like', [LikeController::class, 'store'])->middleware('throttle:60,1');
        Route::delete('/proyectos/{proyecto}/like', [LikeController::class, 'destroy'])->middleware('throttle:60,1');

        // Comentarios: crear y borrar (autor o admin, ComentarioPolicy)
        Route::post('/proyectos/{proyecto}/comentarios', [ComentarioController::class, 'store'])->middleware('throttle:20,1');
        Route::delete('/comentarios/{comentario}', [ComentarioController::class, 'destroy'])->whereNumber('comentario');

        // --- Panel de administración: solo rol admin ---
        Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function (): void {
            Route::apiResource('users', AdminUserController::class)->only(['index', 'store', 'destroy']);
            Route::apiResource('proyectos', AdminProyectoController::class)->only(['index', 'destroy']);
        });
    });
});
