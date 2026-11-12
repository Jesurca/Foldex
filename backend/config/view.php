<?php

/*
| Vistas Blade. La única vista es el HTML base de la SPA (frontend/views/app.blade.php),
| que vive junto al resto del frontend en vez de en resources/views.
*/
return [

    'paths' => [
        base_path('frontend/views'),
    ],

    'compiled' => env('VIEW_COMPILED_PATH', realpath(storage_path('framework/views'))),

];
