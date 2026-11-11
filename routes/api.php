<?php

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
    Route::get('/ping', fn () => response()->json([
        'status' => 'ok',
        'app' => config('app.name'),
        'version' => 'v1',
    ]));

    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:registro');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

    // Lectura pública: catálogos, proyectos y comentarios (si llega un token, se usa para `liked` y `can`).
    Route::get('/materias', [CatalogoController::class, 'materias']);
    Route::get('/docentes', [CatalogoController::class, 'docentes']);
    Route::get('/tecnologias', [CatalogoController::class, 'tecnologias']);
    Route::get('/proyectos', [ProyectoController::class, 'index']);
    Route::get('/proyectos/{proyecto}', [ProyectoController::class, 'show']);
    Route::get('/proyectos/{proyecto}/comentarios', [ComentarioController::class, 'index']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);

        Route::get('/me/tokens', [TokenController::class, 'index']);
        Route::post('/me/tokens', [TokenController::class, 'store']);
        Route::delete('/me/tokens/{token}', [TokenController::class, 'destroy'])->whereNumber('token');

        Route::get('/me/proyectos', [MiEspacioController::class, 'proyectos']);
        Route::get('/me/likes', [MiEspacioController::class, 'likes']);

        Route::post('/proyectos', [ProyectoController::class, 'store'])->middleware('throttle:10,1');
        Route::delete('/proyectos/{proyecto}', [ProyectoController::class, 'destroy']);

        Route::post('/proyectos/{proyecto}/like', [LikeController::class, 'store'])->middleware('throttle:60,1');
        Route::delete('/proyectos/{proyecto}/like', [LikeController::class, 'destroy'])->middleware('throttle:60,1');

        Route::post('/proyectos/{proyecto}/comentarios', [ComentarioController::class, 'store'])->middleware('throttle:20,1');
        Route::delete('/comentarios/{comentario}', [ComentarioController::class, 'destroy'])->whereNumber('comentario');

        Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function (): void {
            Route::apiResource('users', AdminUserController::class)->only(['index', 'store', 'destroy']);
            Route::apiResource('proyectos', AdminProyectoController::class)->only(['index', 'destroy']);
        });
    });
});
