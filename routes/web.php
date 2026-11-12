<?php

use Illuminate\Support\Facades\Route;

// La interfaz es una SPA de React (resources/js). Laravel sirve el mismo HTML para
// cualquier ruta que no sea de la API y TanStack Router decide qué pantalla mostrar.
Route::view('/{any?}', 'app')->where('any', '^(?!api/|mcp/|up$|build/|storage/).*$')->name('spa');
