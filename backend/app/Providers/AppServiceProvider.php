<?php

namespace App\Providers;

// Imports: límites de peticiones (RateLimiter), petición HTTP y utilidades de texto.
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

/** Configuración al arrancar la app: límites de intentos de login y registro. */
class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        RateLimiter::for('login', fn (Request $request): array => [
            Limit::perMinute(5)->by(Str::lower((string) $request->input('email')).'|'.$request->ip()),
            Limit::perMinute(20)->by($request->ip()),
        ]);

        RateLimiter::for('registro', fn (Request $request): Limit => Limit::perMinute(10)->by($request->ip()));
    }
}
