## Universidad Autónoma de Bucaramanga

Facultad de Ingeniería – Desarrollo Backend

## TALLER BÁSICO – INTRODUCCIÓN A LARAVEL

Asignatura: Desarrollo Backend

Docente: Fabián Enrique Suárez Carvajal

## Objetivos del Taller

- 1. Configurar entorno de desarrollo con Laravel

- 2. Crear un proyecto funcional desde cero

- 3. Desarrollar rutas y vistas dinámicas

- 4. Diseñar interfaz con CSS propio (sin Bootstrap)

- 5. Aplicar control de versiones con Git

- 6. Publicar correctamente en GitHub

## Requisitos Previos

## Conocimientos

- HTML y CSS básico

- PHP fundamentals

- Git básico

## Herramientas

- XAMPP o servidor local

- Composer instalado

- Git instalado

- Cuenta en GitHub

## Configuración del Proyecto

## Paso 1: Instalar Laravel globalmente

composer global require laravel/installer

## Paso 2: Crear el proyecto

laravel new mi-perfil


```
Paso 3: Navegar al directorio
cd mi-perfil
Paso 4: Iniciar servidor de desarrollo
php artisan serve
Tu aplicación estará disponible en: http://localhost:8000
Paso 5: Inicializar Git y primer commit
git init
git add .
git commit -m "Proyecto Laravel recién instalado"
```

## Crear Rutas en web.php

Edita el archivo routes/web.php y agrega las siguientes rutas:

```
Route::get('/', function () {
return view('welcome');
});
Route::get('/perfil', function () {
return view('perfil');
});
Route::get('/perfil/intereses', function () {
return view('intereses');
});
Route::get('/perfil/habilidades', function () {
return view('habilidades');
});
Route::get('/perfil/metas', function () {
return view('metas');
});
```

- No olvides: Realizar un commit después de agregar las rutas

```
git add . && git commit -m "Rutas creadas"
```


## Crear Vistas Blade

Crea los siguientes archivos en resources/views/ :

- Vistas Requeridas

- perfil.blade.php

- intereses.blade.php

- habilidades.blade.php

- metas.blade.php

- Contenido Sugerido

- Información personal

- Pasatiempos y gustos

- Skills técnicas

- Objetivos profesionales

⚠️ IMPORTANTE: Debe existir un commit independiente por cada vista creada. No agrupar todas en un solo commit.

## Diseño con CSS Propio (SIN Bootstrap)

Crea un archivo CSS en public/css/estilos.css con:

- Menú de navegación funcional entre páginas

- Paleta de colores personalizada

- Espaciado y márgenes consistentes

- Tipografía legible y profesional

- Diseño responsive (opcional pero recomendado)

- Para enlazar tu CSS en las vistas Blade:

<link rel="stylesheet" href="{{ asset('css/estilos.css') }}">

## Línea de Tiempo del Proyecto

Commit 1 Instalación inicial del proyecto Laravel


Commit 2 Configuración del entorno y archivos base

Commit 3 Creación de rutas en web.php

Commit 4 Vista perfil.blade.php creada

Commit 5 Vista intereses.blade.php creada

Commit 6 Vista habilidades.blade.php creada

Commit 7 Vista metas.blade.php creada

Commit 8

Hoja de estilos CSS implementada

Mejoras de diseño y ajustes visuales

Commit 9

Commit 10

README.md final con documentación

⚠️ ADVERTENCIA: No se acepta un único commit final con todo el trabajo. Se evaluará el proceso completo mediante el historial de commits. Cada cambio significativo debe tener su propio commit descriptivo.

## Progreso Visual del Proyecto


⚠️ CRÍTICO: Si no subes TODOS los enlaces y archivos solicitados en la plataforma TEMA, el taller no será calificado. Verifica antes de enviar.

## Contenido mínimo del README.md:

- Nombre del proyecto

- Descripción breve

- Requisitos del sistema

- Instrucciones de instalación

- Cómo ejecutar el proyecto

- Autor y fecha

## Resultado Esperado

- Una aplicación web Laravel completamente funcional que incluya:

- Múltiples vistas navegables

- Diseño propio con CSS personalizado

- Repositorio en GitHub con historial completo de commits

- README.md profesional

- Código limpio y bien estructurado

Profesionalismo

Organización

Buenas Prácticas

Facultad de Ingeniería – Desarrollo Backend

Taller Básico de Laravel
