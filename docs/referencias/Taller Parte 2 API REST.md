## TALLER ADICIONAL

## Retos Avanzados de API REST con Laravel

Autenticacion - Paginacién - Likes - Bisqueda - Versionado

## 1. Introduccion

Este taller complementa la API basica del proyecto de publicaciones. Después de construir la version principal, puedes profundizar en funcionalidades que hacen que una API sea mas profesional, segura y escalable.

## Objetivo

Implementar mejoras avanzadas en la API para manejar autenticacion, paginacion, likes por usuario, busqueda y versionado de rutas.

## 2. Retos a resolver

## Reto 1 - Autenticacién con Laravel Sanctum

Actualmente cualquier cliente puede crear, comentar y eliminar publicaciones sin identificar al usuario. La mejora consiste en proteger ciertos endpoints para que solo usuarios autenticados puedan realizarlos.

- 1. Instala Laravel Sanctum en el proyecto.


- 2. Configura el middleware de autenticacion en la API.

- 3. Crea un sistema de registro e inicio de sesion para usuarios.

- 4. Protege rutas como:

- © POST /api/posts

- © POST /api/posts/{id}/comments

- © DELETE /api/posts/{id}

- 5. Asocia cada publicacién o comentario con el usuario autenticado.

## Pregunta guia

se puede diferenciar entre un usuario autenticado y un usuario anénimo en

una API REST?

## Recomendacién

```
composer require laravel/sanctum
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan migrate
```

## Objetivo de aprendizaje

Comprender qué significa proteger una como se usan tokens para validar identidad.

## Reto 2 - Paginacién de publicaciones

Cuando la cantidad de publicaciones crece, listar todo en una sola respuesta no es eficiente. Se requiere paginar los resultados para que el cliente obtenga bloques de datos pequefios.

- 1. Modifica el endpoint GET /api/posts .

- 2.Usa ->paginate(10) en la consulta.

- 3. Devuelve informacion de paginacion junto con los datos.

- 4. Prueba diferentes parametros como:

- o ?page=1

- o ?page=2

```
\$posts = 'comments'])
->latest()
->paginate(10);
```


ventaja tiene devolver resultados paginados en lugar de todos los

registros a la vez?

## Objetivo de aprendizaje

Entender como mejorar el rendimiento de una y la experiencia de consumo desde frontends o apps moviles.

## Reto 3 - Sistema de likes mas realista

En la version basica, el like simplemente incrementa un contador. El siguiente paso es hacer que cada usuario solo pueda dar un like por publicacion.

- 1. Crea una tabla pivote para guardar qué usuario dio like a qué post.

- 2. Relaciona User Post con una relacion muchos a muchos. y

- 3. Evita el mismo usuario dé like mas de una vez. que

- 4. Devuelve una respuesta con la cantidad real de likes.

## Ejemplo de disefio

```
Schema: function (Blueprint \$table) {
$table->id();
cascade");
cascade’);
$table->timestamps();
1);
```

## Pregunta guia

; Qué problema tiene un contador global si varios usuarios

a la vez?

## Objetivo de aprendizaje

Aprender a modelar relaciones mas complejas y manejar restricciones de negocio reales.

## Reto 4 - Busqueda por titulo

La API necesita una forma de buscar publicaciones por palabras clave, por ejemplo buscar el texto de un titulo o contenido.

- 1. Modifica el endpoint GET /api/posts .

- 2. un query param llamado search .

- 3. Filtra publicaciones donde el titulo o contenido contenga el valor buscado.

- 4. Prueba consultas como:


- © GET /api/posts?search=viaje

- © GET /api/posts?search=facultad

```
\$posts = 'comments'])
->when($request->search, function ($query, $search) {
'like', "%{$search}%")
'like', "%{$search}%");
->latest()
->get();
```

## Pregunta guia

afecta la busqueda al flujo de la API 'y qué diferencias hay entre filtrar

en SQL y hacerlo en el backend manualmente?

## Objetivo de aprendizaje

Comprender el uso de filtros y bisquedas dinamicas en endpoints REST.

## Reto 5 - Notificaciones por correo al comentar

Cuando alguien comenta en una publicacion, el duefio del post puede recibir un correo informativo.

- 1. Configura el envio de correos con Laravel Mail.

- 2. Genera un Mailable con el asunto “Nuevo comentario”.

- 3. Dispara el correo cuando se guarde un comentario.

- 4. Prueba con un usuario de prueba y un email valido.

```
NewCommentMail(\$comment));
```

## Pregunta guia

; Qué ventajas tiene automatizar notificaciones por correo en una red social o plataforma de contenido?

## Objetivo de aprendizaje

Introduccion a Mailables, eventos y automatizacion de procesos dentro de Laravel.

## Reto 6 - Versionado de la API


Un sistema real casi siempre evoluciona con el tiempo. Por eso se recomienda poner una version en la ruta de la API para evitar romper clientes existentes.

- 1. Crea una ruta como /api/vi/posts .

- 2. Mueve los endpoints actuales a un grupo versionado.

- 3. Configura rutas nuevas en un servicio o controlador especifico.

- 4. Prueba una version vieja siga funcionando mientras la nueva se desarrolla. que

```
Route: () {
Route: :get('/posts', [PostController::class, 'index']);
1s
```

## Pregunta guia

qué es importante versionar una API cuando se agregan cambios

significativos?

## Objetivo de aprendizaje

Comprender cdmo mantener compatibilidad en sistemas de produccion.

## Reto 7 - Documentacién automatica con Swagger o Scribe

para otros desarrolladores sepan como que

Las APIs bien disefiadas deben documentarse

usarlas.

- 1. Instala una libreria de documentacién de APIs.

- 2. Documenta cada ruta, método, body y respuesta.

- 3. Genera una pagina donde los clientes consulten la API.

Herramientas recomendadas:

- \* Swagger

- Scribe

## Pregunta guia

;Por qué es mas facil mantener una API cuando la documentacion esta presente?

## Objetivo de aprendizaje

Valorar la calidad de la documentacion como parte esencial del desarrollo backend.


| Autenticacion con Sanctum |   |   | Avanzado | 2-3 sesiones |
| --- | --- | --- | --- | --- |
| Paginacion |   |   | Medio | 1 sesion |
| Likes | por | usuario | Avanzado | 2 sesiones |
| Busqueda por titulo |   |   | Medio | 1 sesién |
| Notificaciones |   | email por | Avanzado | 2 sesiones |
| Versionado |   |   | Medio | 1 sesion |
| Documentacion Swagger/Scribe |   |   | Medio | 1-2 sesiones |
