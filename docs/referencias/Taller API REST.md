TALLER PRACTICO

## Creacién de una API REST Tipo Facebook con Laravel

Publicaciones -

\- Comentarios - Reacciones

Docente:

FABIAN ENRIQUE SUAREZ CARVAJAL

Institucién:

UNIVERSIDAD AUTONOMA DE BUCARAMANGA

ARQUITECTURA Y DESARROLLO BACKEND

Curso:

## 1. y Objetivos

En el mundo del desarrollo backend moderno, las APIs RESTful son el estandar para comunicar aplicaciones web y servicios entre si. En este taller construiras desde cero una API REST simplificada inspirada en Facebook, donde los usuarios podran crear publicaciones con miltiples imagenes, comentarlas y reaccionar con "Me gusta".

## Objetivo General

Desarrollar una API RESTful funcional en Laravel que simule las interacciones basicas de una red social

(publicaciones con Postman.

fotos, comentarios y reacciones de "Me gusta") y validarla mediante peticiones en

## 2. Requisitos Previos

Antes de iniciar el taller,

de tener instalado:

| Herramienta |   |   |
| --- | --- | --- |
| PHP | 8.10 superior | php -v |
| Comnncer | 2v | comnacar |


| Laravel | 10x0 11x | laravel -V |
| --- | --- | --- |
| Base de datos | sQLite / MySQL / |   |
| Postman | Ultima version |   |
| Editor de cédigo | VS Code / PHPStorm |   |

§ Recomendacién para principiantes

Usa SQLite para evitar configurar un servidor de base de datos. Laravel lo soporta de fabrica creando un archivo database/database.sqlite en laraiz del proyecto.

## 3. Especificacion de la API REST

La API expondr los siguientes endpoints bajo el prefijo /api. :

|   | Ruta | Content-Type | Descripcién | Body / Params | Ccédigo |
| --- | --- | --- | --- | --- | --- |
| POST | /api/posts | multipart/form- | Crear publicacién con fotos | title, content, | 201 |
|   |   | data |   | images[] |   |
|   | /api/posts | application/json | Listar publicaciones con |   |   |
| GET |   |   |   | - |   |
|   |   |   | imégenes y comentarios |   |   |
|   | /api/posts/{id} | application/json | Ver un post individual | - |   |
| POST | /api/posts/{id}/comments | application/json | Agregar comentario | author, |   |
|   |   |   |   | content |   |
| POST | /api/posts/{id}/like | application/json | Incrementar "Me gusta” |   | 200 |
| DELETE | /api/posts/{id} | application/json | Eliminar un post |   | 204 |

## significa cada Codigo?

En esta API, cada endpoint no solo indica la ruta que se debe consumir, sino también el tipo de respuesta que debe devolver el servidor. Como estudiante, debes pensar en la API como una conversacion entre cliente y servidor: el cliente pide algo y el servidor responde con un cédigo HTTP que describe el resultado.

| cédigo | Nombre |
| --- | --- |
|   | significa? Ejemplo aqui |
| HTTP |   |
|   | fue exitosa y se devuelve Listar posts, ver un post, dar like. ok La |
|   | Created El recurso fue creado correctamente en la base de Crear un post o un comentario. |
|   | datos. |
|   | La operacién fue exitosa, pero no hay cuerpo de Eliminar un post. No Content |
|   | respuesta. |
| 400 | Bad Request La solicitud enviada es incorrecta o falta informacion. Falta el titulo o el contenido. |


| 404 | Not Found | El recurso solicitado no existe. | Intentar consultar un post |
| --- | --- | --- | --- |
|   |   |   | inexistente. |
|   | Unprocessable | Los datos llegaron, pero no pasan la validacién. | Archivo no es imagen o texto vacio. |
| 422 |   |   |   |
|   | Entity |   |   |

La idea central es esta: 200 significa que todo sali6 bien; 201 que se cred algo nuevo; 204 que se elimin algo; y 404/422 que hubo un problema con la solicitud o con la informacién enviada. Por eso, al probar la API en Postman, es importante revisar no solo la respuesta JSON, sino también el cédigo HTTP, porque eso nos dice si la operacion fue exitosa o no.

## significa cada método HTTP?

En una API REST, cada método tiene una funcién especifica. Aunque en este taller se usan principalmente GET, POST y DELETE, es importante conocer el propésito general de cada uno:

| Método | Accién principal | Cundo se usa | En este taller |
| --- | --- | --- | --- |
| GET | Leer o consultar informacion. | Cuando el cliente quiere ver datos del servidor. | Listar publicaciones y |
|   |   |   | consultar un post. |
| POST | Crear un nuevo recurso o enviar datos | Cuando se desea registrar algo nuevo, como una | Crear un post, comentar y |
|   | para procesarlos. | publicacién o un comentario. | dar like. |
| DELETE | Eliminar un recurso. | Cuando se quiere borrar algo existente. | Eliminar un post. |
| PUT | Actualizar completamente un recurso. | Cuando se reemplaza toda la informaciéndeun | No se usa en este ejercicio. |
|   |   | registro. |   |
|   | Actualizar solo algunos campos. | Cuando se modifica parcialmente un recurso. | No se usa en este ejercicio. |

La regla facil de recordar es esta: GET sirve para pedir datos, POST para crear datos, y DELETE para quitar datos. En una API REST, el método hace parte del significado de la operacién, no solo de la ruta. Por eso, la misma URL puede hacer cosas diferentes segin el método se use. que

## Arquitectura de una peticién

Cada peticién sigue el siguiente flujo dentro de Laravel:

## 4. Modelo de Datos (Diagrama ER)

La base de datos tendra tres tablas relacionadas de la siguiente forma:


## Relaciones Eloquent

- « Post — Postimage: Un Post tiene muchos (hasMany) Postimage.

- \* Post Comment: Un Post tiene muchos (hasMany) Comment.

- . ge -> Post: Cada pertenece (belongsTo) a un Post.

- « Comment - Post: Cada Comment pertenece (belongsTo) a un Post.

## 5. Versionamiento con Git - Entregas por commits

Durante el desarrollo del taller, cada estudiante debe usar Git como herramienta de control de versiones. La idea no es solo tener el proyecto final, sino demostrar el proceso de construccion paso a paso.

## Importante para la nota

Para esta evaluacion, lo mas importante es que el historial de commits quede claro y documentado. Cada avance debe quedar registrado con un commit descriptivo, como si fuera una entrega parcial del trabajo realizado.

Se recomienda hacer un commit cada vez que se complete un blogue importante del taller, por ejemplo:

- 1. Inicializacién del proyecto > commit: feat: crear proyecto Laravel

- 2. Modelos y migraciones > commit: feat: crear modelos y migraciones para posts, comentarios e imégenes

- 3. Relaciones Eloquent —> commit: feat: configurar relaciones entre Post, Comment y PostImage

- 4. Controladores —» commit: feat: implementar CRUD y légica de likes

- 5. Rutas API > commit: feat: registrar endpoints de la API REST

- 6. Pruebas en Postman — commit: test: validar endpoints en Postman

- 7. Correcciones y ajustes — commit: fix: corregir errores de validacién y almacenamiento

La forma correcta de trabajar es la siguiente:


```
\# Agregar cambios al staging
git add
# Crear commit con mensaje claro
git commit -m "feat: implementar creacion de publicaciones con imagenes"
```

Es recomendable ademas crear una rama principal como main y trabajar con commits limpios y ordenados. Si el proyecto tiene varios avances, cada entrega parcial puede reflejar una etapa del taller.

## Recomendacion docente

El profesor puede revisar el historial de Git para ver como se

lidad del historial de commits cuenta como evidencia de trabajo progresivo.

En resumen: no basta con entregar el proyecto funcionando; también se debe evidenciar el proceso de construccién mediante

commits bien

y organizados. Esto

la solucion, no solo el resultado final. Por eso, la

ponsabilidad, orden y

ion del desarrollo incremental.

## 6. Guia de Implementacion Paso a Paso

Crea el proyecto Laravel e inicializa el enlace simbélico del storage:

```
composer create-project laravel/laravel facebook-api cd facebook-api
# Crear el enlace simbolico para acceder a las imagenes php artisan storage: link
```

Edita el archivo .env vy ajusta las variables DB_CONNECTION , DB_DATABASE , DB_USERNAME y DB_PASSWORD . Para SQLite, crea el archivo database/database.sqlite DB_CONNECTION=sqlite .

Genera los modelos, migraciones y controladores necesarios:

```
php artisan make:model Post -mc
php artisan make:model PostImage -m
php artisan make:model Comment -mc
```

## . posts

```
Schema: :create('posts', function (Blueprint Stable) {
$table->id();
$table->string('title');
$table->unsignedInteger('
$table->timestamps();
```


- ~ post_images

```
schema: :create('post_images', function (Blueprint \$table) {
$table->id();
$table->foreignId('post_id')->constrained()->onDelete( cascade’);
$table->string('image_path');
$table->timestamps();
Hi
```

- ~ comments

```
Schema: :create( function (Blueprint Stable) {
$table->id(); $table->foreignId('post_id')->constrained()->onDelete( $table->string('author'); $table->text('content'); $table->timestamps();
1;
```

Ejecuta las migraciones

php artisan migrate

## Paso 3 - Relaciones Eloquent

## App\Models\Post.php

```
<?php
namespace App\Models;
use Tlluminate\Database\Eloquent\Model;
class Post extends Model
protected $fillable = ['title', 'content', 'likes count'];
public function images()
{
return Sthis->hasMany(PostImage: :class);
public function comments()
{
return Sthis->hasMany(Comment: :class);
```

## App\Models\PostImage.php

```
<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PostImage extends Model
{
```


```
protected \$fillable = ['post_id', 'image_path'];
public function post()
{
return Sthis->belongsTo(Post:
```

## App\Models\Comment.php

```
<?php
namespace App\Models;
use
class Comment extends Model
{
protected $fillable = 'content'];
public function post()
{
return Sthis->belongsTo(Post:
```

## App\Http\Controllers\PostController.php

```
<?php
namespace App\Http\Controllers;
use App\Models\Post
use App\Models\PostImage
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
class PostController extends Controller
{
public function index()
{
$posts = Post::with(['images', 'comments'])
->latest()
->get()
(Spost) {
$post->images->transform(function ($img) {
return $img;
bi
return $post;
bi
= asset('storage/' . $img->image path)
return 200);
public function show($id)
{
$post = Post: :with(['images', 'comments'])->findorFail($id)
$post->images->transform(function ($img) {
$img->image_url = asset('storage/' .
return $img;
```


```
return response()->json(\$post, 200);
public function store(Request $request)
{
Srequest->validate([
‘title’ => 'required|string|max:255',
‘content’ => 'required|string’,
'images.*' => 'image|mimes: jpg, gif |max:2048"
Di
$post = Post: :create([
‘title! Srequest->title,
=> Srequest->content,
n;
if ($request->hasFile('images')) {
foreach (Srequest->file('images') as $file) {
$path = $file->store('posts’, 'public');
PostImage: :create([
‘post_id' => $post->id,
=> $path,
n;
return response()->json($post->load('images'), 201);
public function Llike($id)
{
$post = Post: :findorFail($id);
$post->increment Likes_count');
return response()->json([
‘message’ => 'Me gusta afiadido correctamente',
=> $post->likes_count,
1, 200);
public function
{
$post =
foreach ($post->images as $image) {
storage: :disk('public')->delete($image->inage_path);
Spost->delete();
return response()->json(null, 204);
```

## App\Http\Controllers\CommentController.php

```
<?php
namespace App\Http\Controllers;
use App\Models\Post;
use Illuminate\Http\Request;
class CommentController extends Controller
{
public function index($postId)
```


```
\$post = Post:
return response()->json($post->comments()->latest()->get(), 200);
public function store(Request Srequest, SpostId)
{
"author" 'required|string|max:160',
ntent' => 'required|string'
1;
$post = Post: :findorFail($postId);
Scomment = $pos )->create([
author,
=> $request->content,
Edita Toutes/api.php :
<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PostController;
App\Http\Controllers\CommentController;
Route: :get('/posts', [PostController::class, 'index']);
Route: :get('/posts/{id}', [Postcontroller::class, 'show']);
Route: :post('/posts', [PostControllel ss, 'store'l);
Route: :delete('/posts/{id}', [PostController::class, 'destroy']);
ost('/posts/{id}/like', [Postcontroller::class, 'like']);
e::get('/posts/{id}/comments’, [CommentController:
mments', [CommentController::class,
php artisan serve # Servidor corriendo en http://127.0
```

## 7. Guia de Pruebas en Postman

## -

En lugar de importar un archivo externo, vamos a crear la colecciéon manualmente para aprender a organizar las peticiones de la API. Esto ayuda a que el estudiante entienda funciona un entorno de pruebas real en Postman.


## Paso 1- Lrear la coleccion

- 1. Abre Postman.

- 2. Enel panel izquierdo, haz clic en Collections.

- 3. Presiona el botén New Collection.

- 4. Escribe el nombre: Facebook API - Taller Backend.

- 5. Haz clic en Create.

Con esto tienes una carpeta donde guardarés todas las peticiones relacionadas con la API. La coleccién funciona como un ya contenedor organizar tus pruebas por funcionalidad. para

## Paso 2 - Crear variables de entorno

Para evitar escribir la misma URL muchas veces, crea una variable llamada {{base_url}} con este valor:

## http://127.0

Luego en cada request puedes usar la URL completa como:

## {{base_url}}/api/posts

Esto hace mas facil reusar la coleccin y cambiar la direccion si luego se despliega el proyecto en otro servidor.

## Paso 3 - Crear la primera peticién

Dentro de la coleccion, haz clic en Add Request y crea esta peticion:

| Nombre | Crear publicacién con imagenes |
| --- | --- |
| Método | POST |
| URL | {{base_url}}/api/posts |
| Headers | Accept: application/json |
| Body | form-data |
| title (Text) | Mi primera publicacién |
| content (Text) | Hola a todos, comparto fotos de mi viaje. |
| images[] (File) | Selecciona foto 1 |
| images[] (File) | Selecciona foto 2 |
| Resultado esperado | 201 Created |

Explicacion: esta peticion crea un nuevo post y guarda archivos en el storage del proyecto. Como se envia con multipart/form-

data Postman permite subir archivos reales y también datos de texto. ,

## Prueba 1 - Crear Publicacién con Imagenes

Método


| URL | {{base_url}}/api/posts |
| --- | --- |
| Headers | Accept: application/json |
| Body | form-data |
| title (Text) | Mi primera publicacién |
| content (Text) | Hola a todos, comparto fotos de mi viaje. |
| images] (File) | Selecciona foto 1 |
| images[] (File) | Selecciona foto 2 |
| Resultado esperado | 201 Created |

## Prueba 2 - Listar Publicaciones

Dentro de la misma coleccién, agrega otra peticién:

| Nombre | Listar publicaciones |
| --- | --- |
| Método | GET |
| URL | {{base_url}}/api/posts |
| Headers | Accept: application/json |
| Resultado esperado | 200 OK con array de posts |

Explicacion: esta peticion solicita todos los posts almacenados. El servidor responde con un JSON que incluye el contenido del post, sus comentarios e imagenes.

## Prueba 3 - Agregar Comentario

| Nombre | Agregar comentario |
| --- | --- |
| Método | POST |
| URL | {{base_url}}/api/posts/1/comments |
| Headers | Accept: application/json, Content-Type: application/json |
| Body (raw JSON) | {"author": "Carlos Gémez", "content": buenas fotos!"} |
| Resultado esperado | 201 Created |

Explicacion: se usa POST porque se esta creando un nuevo comentario asociado a un post existente. El servidor valida que tanto el nombre del autor como el contenido estén completos.

## Prueba 4 - Dar Me Gusta

| Nombre | Dar like a publicacién |
| --- | --- |
| Método | POST |


| URL | {{base_url}}/api/posts/1/like |
| --- | --- |
| Body | No requiere |
| Resultado esperado | 200 OK con likes_count incrementado |

Explicacion: aunque no se envian datos en el cuerpo, el servidor realiza una operacion de actualizacién: incrementa el contador de likes del post.

## Prueba 5 - Eliminar Publicacién

| Nombre | Eliminar publicacion |
| --- | --- |
| Método | DELETE |
| URL | {{base_url}}/api/posts/1 |
| Resultado esperado | 204 No Content |

Explicacion: esta solicitud borra el post y también elimina sus imagenes asociadas del storage. El servidor responde con 204 porque la operacion fue exitosa pero no devuelve contenido en el cuerpo.

## § Recomendacién para el estudiante

Crea cada peticion dentro de la misma coleccién y prueba una por una. No intentes ejecutar todo al mismo tiempo. Primero crea un post, luego lista, luego comenta, luego da like y finalmente elimina. Asi aprendes el flujo real de una API REST.

## Exportar la coleccién y guardarla en Git

Una vez termines de probar todas las peticiones, debes exportar la coleccién creada en Postman para dejar evidencia del trabajo realizado.

- 1. En Postman, abre la coleccién Facebook API - Taller Backend.

- 2. Haz clic en el botén Export.

- 3. Selecciona el formato Collection v2.1.

- 4. Guarda el archivo con un nombre claro, por ejemplo: json .

- 5. En tu repositorio local, aiade el archivo al proyecto.

- 6. Haz un commit exclusivo para la coleccién exportada.

```
\# ejemplo de commit para la exportada
git add facebook-api-postman-collection. json git commit -m "test: exportar coleccién de Postman”
```

Este commit es importante porque demuestra que el estudiante no solo construy® la API, sino que también valid cada endpoint con pruebas reales en Postman. En la nota, eso cuenta como evidencia de verificacion funcional.

## Organizacién final recomendada en Postman

- \+ Facebook API - Taller Backend


- Lit

- \> Listar publicaciones

- © Agregar comentario

- © Dar like a publicacion

- © Eliminar publicacion

## 8. Checklist - "Dejarlo Funcionando"

Al terminar el taller, verifica que cada uno de los siguientes puntos esté cumplido:

|   |   | Evidencia |
| --- | --- | --- |
|   | Proyecto Laravel creado y corriendo en http://127.0.0.1:8000 | Captura de php artisan serve |
| [0 | Enlace simbdlico de storage creado | public/storage existe |
| [0 | Base de datos migrada con tablas posts, post_images, comments | php artisan migrate:status |
| [m} | POST /api/posts crea post y guarda imagenes correctamente | Respuesta 201 con JSON |
| Oa | GET /api/posts lista los posts con image_url completa | Captura Postman |
|   | POST /api/posts/{id}/comments agrega comentario | Respuesta 201 |
| [J | POST /api/posts/{id}/like incrementa likes_count | Respuesta 200 |
| [J | DELETE /api/posts/{id} elimina post e imagenes fisicas | Respuesta 204 |
| [m} | Coleccion Postman completa y funcional | .json exportado |
| Oa | Repositorio en GitHub con README.md | URL del repo |

## 9. Glosario de Términos

|   | Def |
| --- | --- |
| API REST | Interfaz de programacion que sigue los principios REST para comunicar sistemas via HTTP. |
| Endpoint | URL especifica de la API que realiza una funcién determinada. |
| Eloquent | ORM (Object-Relational Mapping) de Laravel para interactuar con la base de datos usando modelos. |
| Migracién | Archivo PHP que define la estructura de una tabla en la base de datos. |
| Storage | Sistema de archivos de Laravel para guardar imagenes, PDFs, etc. |
| multipart/form-data | Formato HTTP usado para enviar archivos junto con otros datos. |
| Postman | Herramienta grafica para probar APIs HTTP. |


| Middleware | Capa intermedia que procesa peticiones antes de llegar al controlador. |
| --- | --- |
| CSRF | Cross-Site Request Forgery, mecanismo de seguridad que Laravel activa por defecto en rutas web. |

## 10. Recursos Adicionales

- « Documentacion oficial de Laravel: https://laravel.com/docs

- « Laravel Eloquent Relationships: https://laravel.com,

- « File Storage en Larav : https://laravel.com/docs/filesystem

- « Postman Learning Center: https://learning.postman.com

- « REST API Tutorial: https:/restfulapi.net

## 11. Anexos

## Anexo A - Estructura de carpetas del proyecto

en el taller! La mejor forma de aprender es romper cosas y arreglarlas.
