Flujo Construccién Interactiva (Bottom-Up)

## Taller Practico: CRUD de Productos y Proteccion Final con Autenticacion

Desarrollo a paso: primero construimos toda la funcionalidad del CRUD y, paso como paso final, aplicamos la capa de seguridad y bloqueo de accesos.

## | 1. Estrategia de Aprendizaje

Construiremos el proyecto mediante un enfoque incremental de 5 pasos:

- 1. Paso 1: Reglas de validacion para proteger la integridad de los datos.

- 2. Paso 2: Légica del controlador (Crear, Leer, Editar, Eliminar).

- 3. Paso 3: Interfaz gréfica en Blade (Vistas y Formularios funcionales sin restricciones).

- 4. Paso 4: Instalacion del médulo de autenticaciéon (Laravel Breeze).

- 5. Paso 5 (Cierre de Seguridad): Agrupacion de rutas con Middleware auth y proteccién visual con directivas @auth de Blade.

## | 2. Paso a Paso Guiado

## PASO 1: Centralizar la Validacién con FormRequest

Genera la clase de validacion en la terminal:

php artisan make:request ProductRequest

Edita app/Http/Requests/ProductRequest.php :

<?php

namespace App\Http\Requests;


use Illuminate\Foundation\Http\FormRequest;

```
class ProductRequest extends FormRequest
@yield('content')
</main>
</body>
</html>
public function rules():
{
return [
'required|string|max:255",
'descriptio
```

- Separacion de Responsabilidades: Mantiene el controlador limpio de reglas de validacion.

- Seguridad de Base de Datos: La regla exists:categories,id previene errores de clave fordnea en la base de datos al validar que la categoria elegida exista realmente.

Crea el controlador con los métodos del CRUD:

```
php artisan make:controller ProductController --model=Product
```

Implementa la I6gica en app/Http/Controllers/ProductController.php :


```
namespace App\Http\Controllers;
use App\Models\Product;
use App\Models\Category;
use App\Http\Requests\ProductRequest;
class ProductController extends Controller
{
public function index()
{
// Carga ansiosa para evitar el problema de rendimiento N+1
$products = Product::with('category')->latest()->paginate(10);
return view('products.index', compact('products'));
public function create()
{
$categories = Category::all();
return view('products.create', compact('categories'));
public function store(ProductRequest $request)
{
Product: :create($request->validated());
return 'Producto
creado correctamente.');
}
public function show(Product $product)
{
return view('products.show', compact('product'));
public function $product)
{
$categories = Category::all();
return view('products.edit', compact('product', 'categories'));
public function $request, Product $product)
```


```
\$product->update(\$request->validated());
return redirect()->route('products.index')->with('success', 'Producto
actualizado correctamente.');
}
public function $product)
{
$product->delete();
return 'Prod
eliminado correctamente.');
}
```

- Route Model Binding ( Product \$product ): Laravel busca autométicamente en la BD el registro por su ID y lanza un error 404 si el producto no existe.

- Asignacién Masiva Segura ( \$request->validated() ): Inyecta de forma limpia y segura solo los campos que superaron las reglas del ProductRequest .

Disefiaremos las plantillas para listar, crear y editar productos. Las rutas base en routes/web.php deben estar declaradas para permitir el funcionamiento de las vistas:

// Declaracién inicial de rutas en routes/web.php para probar la interfaz use App\Http\Controllers\ProductController;

```
:resource( 'products', ProductController::class);
```

A. Layout Base ( resources/views/layouts/app.blade.php)

```
<!DOCTYPE html>
<html lang="es">
```


```
<head>
<meta charset="UTF-8">
de Productos</title>
</head>
<body>
<nav>
<a href="{{ route('products.index') }}">Catdlogo</a>
<a href="{{ route('products.create') }}">+ Crear Producto</a>
</nav>
<main>
@if(session('success'))
<div style="background: #dle7dd; color: #@f5132; padding: 1@px;
margin-bottom: 15px;">
{{ session('success') }}
</div>
@endif
</main>
</body>
</html>
```

## B. Tabla de Productos ( resources/views/products/index.blade.php )

```
@extends ('layouts.app')
<hl>Lista de Productos</h1>
<table border="1" cellpadding="8" cellspacing="0">
<thead>
<tr>
<th>Nombre</th>
<th>Categoria</th>
<th>Precio</th>
<th>Acciones</th>
</tr>
</thead>
<tbody>
```


```
<td>{{ \$product->name }}</td>
<td>{{ $product->category->name }}</td>
<td>${{ number_format($product->price, 2) }}</td>
<td>
<a href="{{ route('products.show', $product) }}">Ver</a>
<a href="{{ route('products.edit', $product) }}">Editar</a>
<form action="{{ route('products.destroy', $product) }}"
method="POST" style="display:inline;">
@csrf
<button type="submit" onclick="return confirm(';Eliminar
</form>
</td>
</tr>
@endforeach
</tbody>
</table>
{{ $products->links() }}
@endsection
```

## C. Formulario de ( resources/views/products/create.blade.php)

```
@extends ('layouts.app')
@section('content')
<h1>Nuevo Producto</h1>
<form action="{{ route('products.store') }}" method="POST">
@csrf
<div>
<label>Nombre:</label><br>
<input type="text" name="name" value="{{ old('name') }}">
@error('name') <br><small style="color:red;">{{ $message }}</small>
@enderror
</div><br>
```


```
<div>
<select name="category_id">
<option value="">-- Seleccione --</option>
@foreach($categories as $category)
<option value="{{ $category->id }}" {{ old('category_id') ==
$category->id ? 'selected' : '' }}>
{{ $category->name }}
</option>
@endforeach
</select>
@error('category_id') <br><small style="color:red;">{{ $message }}
</small> @enderror
</div><br>
<div>
<label>Precio:</label><br>
<input type="number" step="0.01" name="price" value="{{ old('price')
<br><small style="color:red;">{{ $message }}</small>
@enderror
</div><br>
<div>
<label>Descripcidn:</label><br>
<textarea name="description">{{ old('description') }}</textarea>
@error('description') <br><small style="color:red;">{{ $message }}
</small> @enderror
</div><br>
<button type="submit">Guardar Producto</button>
</form>
@endsection
```

## ¢ ¢Para qué sirve esto estratégicamente?

- @csrf : Protege la aplicacién contra ataques de falsificacién de peticion en sitios cruzados. Sin el token, Laravel aborta la con un error 419.


- old('campo') : Mantiene los datos rellenados por el usuario si la validacién falla, evitando tener que escribir todo de nuevo.

Una vez comprobado que el CRUD funciona perfectamente, instalamos la estructura de Login/Registro oficial con Laravel Breeze:

```
composer require laravel/breeze --dev
php artisan breeze:install blade
php artisan migrate
npm install && npm run dev
```

Ahora "cerramos el candado" aplicando seguridad tanto en el backend (servidor) como en el frontend (vista).

- 1. Proteccion de Servidor en routes/web.php

Reemplaza la definicién bésica del recurso por la separacién explicita entre rutas publicas y restringidas:

## <?php

```
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
// --- ACCESO PUBLICO (Solo consulta) ---
[ProductController::class, 'index']);
Route: :get('/products', [ProductController lass, 'index'])-
>name ('products.index');
Route: :get('/products/{product}', [Produ ontroller::class, 'show'])-
>name
// --- ACCESO RESTRINGIDO (Requiere iniciar sesi
Route: () {
[ProductController::class, 'create'])-
```


Route: :post('/products', [ProductController::class, 'store'])-

Route: :get('/products/{product}/edit', [ProductController::class,

Route: :put('/products/{product}', [ProductController::class, 'update'])-

>name

Route: :delete('/products/{product}', [ProductController::class

require _ DIR__.'/auth.php';

## 2. Visual en Blade

Actualiza la barra de navegacion en

resources/views/layouts/app.blade.php usando las directivas @auth @guest

```
<nav>
<a href="{{ route('products.index')
{{-- Visible UNICAMENTE para usuarios autenticados --}}
@auth
<span>Hola, {{ Auth::user()->name }}</span>
<a href="{{ route('products.create') }}">+ Crear Producto</a>
<form action="{{ route('logout') }}" method="POST"
style="display:inline;">
@csrf
<button type="submit">Cerrar Sesidén</button>
</form>
@endauth
{{-- Visible UNICAMENTE para visitantes no autenticados --}}
@guest
<a href="{{ route('login') }}">Iniciar Sesién</a>
<a href="{{ route('register') }}">Registrarse</a>
@endguest
</nav>
```


Oculta los botones de moditicacion en la tabla (

```
<td>
<a href="{{ route('products.show', $product) }}">Ver</a>
{{-- Opciones administrativas ocultas para visitantes --}}
@auth
<a href="{{ route('products.edit', $product) }}">Editar</a>
<form action="{{ route('products.destroy', $product) }}"
POST" style="display:inline;">
@csrf
@method( 'DELETE")
<button type="submit" onclick="return
confirm('¢Eliminar?')">Eliminar</button>
@endauth
</td>
```

- Doble Capa de Proteccién: La directiva @auth oculta la interfaz visualmente mejorar la experiencia de usuario (UX). Sin embargo, para la seguridad real la otorga el middleware(['auth']) en el servidor, impidiendo el paso incluso si alguien intenta forzar la URL manualmente.

## 3. Conclusion de Aprendizaje

Al finalizar el taller, los estudiantes habran experimentado cémo construir una solucién funcional completa antes de asegurar sus accesos, aplicando las mejores préacticas de validacion, arquitectura de controladores seguridad por capas en Laravel. y
