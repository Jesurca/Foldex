<?php

use App\Http\Controllers\Api\V1\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\V1\AuthController;
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

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);

        Route::get('/me/tokens', [TokenController::class, 'index']);
        Route::post('/me/tokens', [TokenController::class, 'store']);
        Route::delete('/me/tokens/{token}', [TokenController::class, 'destroy'])->whereNumber('token');

        Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function (): void {
            Route::apiResource('users', AdminUserController::class)->only(['index', 'store', 'destroy']);
        });
    });
});
