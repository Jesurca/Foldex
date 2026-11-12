<?php

// Rutas web: solo una, que entrega la SPA de React.

// Imports: la fachada de rutas.
use Illuminate\Support\Facades\Route;

// La interfaz es una SPA de React (frontend/js). Laravel sirve el mismo HTML (frontend/views/app.blade.php)
// para cualquier ruta que no sea de la API, del MCP ni archivos, y TanStack Router decide qué pantalla mostrar.
Route::view('/{any?}', 'app')->where('any', '^(?!api/|mcp/|up$|build/|storage/).*$')->name('spa');
