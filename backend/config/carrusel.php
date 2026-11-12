<?php

/*
| Carrusel de la portada. Las fotos se leen de esta carpeta cada vez que alguien abre la página:
| basta con copiar o borrar archivos ahí, sin recompilar el frontend.
*/
return [

    // Carpeta con las fotos (por defecto frontend/contexto_img).
    'ruta' => env('CARRUSEL_RUTA', base_path('frontend/contexto_img')),

    // Formatos aceptados (SVG no: puede llevar scripts).
    'extensiones' => ['jpg', 'jpeg', 'png', 'webp', 'avif'],

];
