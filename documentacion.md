# Documentación técnica — Foldex, Repositorio de Proyectos UNAB (API Laravel)

Bitácora de cada paso del backend: qué se hizo, por qué, cómo probarlo y el commit asociado.

## Estructura de carpetas relevante
Desde el Paso 9 el código está separado en `backend/` y `frontend/` dentro del mismo proyecto Laravel (ver Paso 9).

```
backend/                       ← todo lo de Laravel
  app/Actions/                 ← lógica de negocio reutilizable (API y MCP)
  app/Enums/Role.php           ← roles estudiante/admin
  app/Http/Controllers/Api/V1/ ← controladores versionados
  app/Http/Middleware/         ← EnsureRole (role:admin), ForzarRespuestaJson
  app/Http/Requests/           ← FormRequests (validación)
  app/Http/Resources/          ← API Resources (contrato JSON)
  app/Mcp/                     ← servidor MCP (Servers/ProyectosServer + Tools/) — ver Paso 6
  app/Models/  app/Policies/   ← modelos Eloquent y permisos por registro
  config/                      ← auth.php (allowed_domain, admin), cors.php, services.php (github), view.php
  database/                    ← migrations/ factories/ seeders/
  lang/es/                     ← mensajes de validación en español
  routes/api.php               ← /api/v1/...
  routes/ai.php                ← /mcp/proyectos (servidor MCP)
  routes/web.php               ← cualquier otra ruta devuelve la SPA
frontend/                      ← interfaz React (SPA) — ver Paso 5
  js/                          ← app.tsx, routes/, components/, lib/, test/
  css/app.css                  ← Tailwind v4 y tema
  views/app.blade.php          ← HTML base de la SPA
  contexto_img/                ← fotos del carrusel de la portada — ver Paso 8
bootstrap/app.php              ← arranque: rutas, middleware, errores y rutas de backend/
tests/Feature/                 ← pruebas Pest contra PostgreSQL (Pest exige tests/ en la raíz)
docker-compose.yml             ← PostgreSQL local
```

---

## Paso 1 — Entorno: Laravel 13 + PostgreSQL + Sanctum + Pest
**Qué se hizo:** proyecto Laravel 13.40 sobre PHP 8.4, PostgreSQL 17 en Docker, Sanctum (`install:api`), ruta `GET /api/v1/ping`, idioma español, CORS limitado a `FRONTEND_URL`, Pest con BD de pruebas PostgreSQL.
**Por qué:**
- La máquina tenía PHP 8.2 (XAMPP) y Laravel 13 exige 8.3+ → se instaló PHP 8.4 NTS con `winget` (no se tocó XAMPP).
- PostgreSQL en Docker (`docker-compose.yml`) en vez de instalarlo en Windows: mismo motor que Azure, se levanta/borra con un comando.
- Las pruebas corren contra PostgreSQL (`proyectos_unab_test`), no SQLite, para detectar diferencias de motor (`ilike`, PK compuestas, enums) antes de producción.
- `config/database.php` con `'default' => env('DB_CONNECTION', 'pgsql')` (requisito Taller 3).
- CORS: `allowed_origins` sale de `FRONTEND_URL` (admite varios separados por coma); nunca `*` porque el frontend Lovable vive en otro dominio.
- `config('auth.allowed_domain')` ← `ALLOWED_EMAIL_DOMAIN` para no quemar el dominio en el código.
- Mensajes en español con `laravel-lang/common` (`php artisan lang:add es`) y `APP_LOCALE=es`.
**Comandos:**
```bash
winget install --id PHP.PHP.NTS.8.4 -e --scope user   # + habilitar pdo_pgsql, pgsql, intl, zip, mbstring… en php.ini
composer create-project laravel/laravel temp_app "^13.0"   # luego mover todo a la raíz y borrar temp_app
docker compose up -d
php artisan install:api
composer require pestphp/pest pestphp/pest-plugin-laravel laravel-lang/common --dev -W
php artisan lang:add es
docker exec proyectos_unab_db psql -U proyectos -d proyectos_unab -c "CREATE DATABASE proyectos_unab_test"
php artisan config:publish cors
./vendor/bin/pest
```
**Archivos:** `docker-compose.yml`, `.env.example`, `config/database.php`, `config/auth.php`, `config/cors.php`, `routes/api.php`, `bootstrap/app.php`, `phpunit.xml`, `tests/Pest.php`, `tests/Feature/PingTest.php`, `lang/es/*`.
**Cómo probar:** `GET {{base_url}}/api/v1/ping` con `Accept: application/json` → **200** `{"status":"ok","app":"Foldex","version":"v1"}`.
**Commit:** `config: configurar PostgreSQL, Sanctum, CORS y Pest`

---

## Paso 2 — Modelo de datos: migraciones, modelos, relaciones, factories y seeders
**Qué se hizo:** 8 migraciones en orden de dependencia (`materias` → `docentes` → `tecnologias` → `users` (role/programa/semestre) → `proyectos` → `proyecto_tecnologia` → `comentarios` → `likes`), enum `App\Enums\Role`, 6 modelos con relaciones en ambos lados, factories con correos `@unab.edu.co` y seeders de catálogos + admin inicial.

**Diagrama ER (DBML para dbdiagram.io):**
```dbml
Table users { id bigint [pk]; name varchar; email varchar [unique]; password varchar; role varchar [note: 'estudiante|admin']; programa varchar; semestre smallint; email_verified_at timestamp; created_at timestamp; updated_at timestamp }
Table materias { id bigint [pk]; codigo varchar [unique]; nombre varchar; semestre smallint }
Table docentes { id bigint [pk]; nombre varchar; email varchar [unique] }
Table tecnologias { id bigint [pk]; nombre varchar [unique] }
Table proyectos { id bigint [pk]; user_id bigint [ref: > users.id]; materia_id bigint [ref: > materias.id]; docente_id bigint [ref: > docentes.id]; titulo varchar; slug varchar [unique]; contexto text; repositorio_url varchar; video_url varchar [null]; periodo varchar; created_at timestamp; updated_at timestamp; deleted_at timestamp }
Table proyecto_tecnologia { proyecto_id bigint [ref: > proyectos.id]; tecnologia_id bigint [ref: > tecnologias.id]; indexes { (proyecto_id, tecnologia_id) [pk] } }
Table comentarios { id bigint [pk]; proyecto_id bigint [ref: > proyectos.id]; user_id bigint [ref: > users.id]; contenido text; created_at timestamp; updated_at timestamp; deleted_at timestamp }
Table likes { user_id bigint [ref: > users.id]; proyecto_id bigint [ref: > proyectos.id]; created_at timestamp; indexes { (user_id, proyecto_id) [pk] } }
```

**Relaciones Eloquent:**
| Modelo | Relación |
|---|---|
| `User` | `proyectos()` hasMany · `comentarios()` hasMany · `likes()` belongsToMany Proyecto (tabla `likes`) |
| `Proyecto` | `user()`, `materia()`, `docente()` belongsTo · `tecnologias()` belongsToMany · `comentarios()` hasMany · `likes()` belongsToMany User |
| `Materia` / `Docente` | `proyectos()` hasMany |
| `Tecnologia` | `proyectos()` belongsToMany |
| `Comentario` | `proyecto()`, `user()` belongsTo |

**Por qué:**
- `likes` y `proyecto_tecnologia` con **PK compuesta**: la BD misma impide likes duplicados (no depende del código). Se descartó un contador `likes` en `proyectos` (se desincroniza y no sabe quién dio like).
- `restrictOnDelete` hacia `materias`/`docentes`/`tecnologias`: no se puede borrar un catálogo en uso. `cascadeOnDelete` desde `users`/`proyectos` (pendiente de confirmar con el equipo, pregunta abierta 5).
- `softDeletes` en `proyectos` y `comentarios`: el admin modera sin perder evidencia.
- `role` como enum PHP (`Role::Estudiante`/`Role::Admin`) con cast en el modelo y `enum` en BD (PostgreSQL crea un `CHECK`): solo valores válidos en ambas capas. Valor por defecto `estudiante` en BD y en `$attributes`.
- `role`, `user_id`, `proyecto_id` y `slug` **fuera de `$fillable`**: el autor sale del token y el rol solo lo cambia un admin/seeder.
- `slug` lo genera el modelo (evento `creating`) a partir del título, único incluso contra proyectos borrados lógicamente (`withTrashed`).
- Índices en `materias.semestre`, `proyectos.created_at`, `proyectos.periodo`, `likes.proyecto_id`, `proyecto_tecnologia.tecnologia_id` para los filtros y el orden "recientes/populares" de la Fase 5.
- Admin inicial desde `ADMIN_EMAIL`/`ADMIN_PASSWORD` (vía `config/auth.php`, compatible con `config:cache`). Valida el dominio y en producción exige contraseña explícita. En local, si `ADMIN_PASSWORD` está vacío, usa `Admin12345*`.
- Materias: plan de Ingeniería de Sistemas con componente de programación; **códigos referenciales** (validar con el pensum oficial). Docentes: solo el docente del curso es real; el resto son marcadores "(por definir)" para no inventar nombres de personas reales.
- No se siembran proyectos: según el plan se crean por Postman.
- **Preguntas abiertas no resueltas** (se dejó el valor por defecto del plan): sin rol `docente`, un autor por proyecto, sin `MustVerifyEmail`, publicación directa con moderación posterior, cascade al borrar usuario, sin `categoria`.

**Comandos:**
```bash
php artisan make:model Materia -mfs   # ídem Docente, Tecnologia, Proyecto; Comentario -mf
php artisan make:migration add_perfil_y_rol_to_users_table --table=users
php artisan make:migration create_proyecto_tecnologia_table
php artisan make:migration create_likes_table
php artisan make:seeder AdminSeeder
php artisan migrate:fresh --seed
./vendor/bin/pest --filter=ModeloDatos
```
**Archivos:** `app/Enums/Role.php`, `app/Models/{User,Materia,Docente,Tecnologia,Proyecto,Comentario}.php`, `database/migrations/2026_11_10_0558*`, `database/factories/*`, `database/seeders/{Database,Materia,Docente,Tecnologia,Admin}Seeder.php`, `config/auth.php`, `.env.example`, `tests/Feature/ModeloDatosTest.php`.
**Cómo probar:** `php artisan migrate:fresh --seed` → todas las migraciones `DONE`; `./vendor/bin/pest --filter=ModeloDatos` → 9 pruebas verdes (relaciones, slug, PK compuesta de likes, restrict, cascade, softDeletes, fillable).
**Commit:** `feat: crear modelo de datos con migraciones, modelos y seeders`

---

## Paso 3 — Autenticación con Sanctum, dominio @unab.edu.co, roles y Policies
**Qué se hizo:** endpoints `register`, `login`, `logout`, `me`, tokens personales (`/me/tokens`), panel `/admin/users` protegido con `role:admin`, Policies `ProyectoPolicy`/`ComentarioPolicy` y errores JSON uniformes en español.

**Endpoints:**
| Método | Ruta | Acceso | Respuesta |
|---|---|---|---|
| POST | `/api/v1/register` | público (`throttle:registro` 10/min) | 201 `{token, user}` |
| POST | `/api/v1/login` | público (`throttle:login` 5/min por correo+IP) | 200 `{token, user}` |
| POST | `/api/v1/logout` | auth | 204 |
| GET | `/api/v1/me` | auth | 200 `{data: user}` |
| GET/POST | `/api/v1/me/tokens` | auth | 200 lista · 201 `{token, aviso}` (token visible una sola vez) |
| DELETE | `/api/v1/me/tokens/{id}` | auth (solo tokens propios) | 204 · 404 |
| GET | `/api/v1/admin/users?q=&role=` | `role:admin` | 200 paginado `{data, links, meta}` |
| POST | `/api/v1/admin/users` | `role:admin` | 201 crea **admin** |
| DELETE | `/api/v1/admin/users/{user}` | `role:admin` | 204 · 403 si es él mismo |

**Por qué:**
- **Sanctum con token Bearer** (no cookies/sesión) porque el frontend Lovable está en otro dominio y el servidor MCP también consume la API con token.
- **Dominio en dos capas (registro y login)** mediante el trait `ValidaCorreoInstitucional`: `prepareForValidation()` normaliza `strtolower(trim())` y la regla `ends_with:@unab.edu.co` (tomada de `config('auth.allowed_domain')`) se reutiliza en `RegisterRequest`, `LoginRequest` y `StoreAdminUserRequest`. Se incluye la `@` para que `x@unab.edu.co.fake.com` y `x@falsounab.edu.co` fallen. Mensaje: "Solo se permiten correos institucionales @unab.edu.co".
- **Registro público siempre `estudiante`**: la Action `RegistrarEstudiante` asigna el rol explícitamente y `role` no es fillable, así que `"role":"admin"` en el body se ignora. Los admins solo se crean por `AdminSeeder` o `POST /admin/users` (Action `CrearAdmin`).
- **Login con mensaje genérico** (`auth.failed`, 422) tanto si el correo no existe como si la contraseña falla: no revela qué cuentas existen. Throttle por correo+IP contra fuerza bruta.
- **Lógica en Actions** (`app/Actions/Auth`): `RegistrarEstudiante`, `IniciarSesion`, `EmitirToken`, `CrearAdmin`, `EliminarUsuario`. Los controladores solo orquestan; el MCP podrá reutilizarlas.
- **Middleware `EnsureRole`** (alias `role`) para grupos completos (`/admin`); **Policies** para reglas sobre un registro concreto (dueño o admin). Se registran solas por convención de nombres (`Proyecto` → `ProyectoPolicy`).
- Un admin no puede borrarse a sí mismo (evita quedarse sin administradores por error) → 403 con mensaje claro. Al eliminar una cuenta se revocan sus tokens en la misma transacción.
- **`ForzarRespuestaJson`** antepuesto al grupo `api`: aunque el cliente olvide `Accept: application/json`, la validación devuelve 422 JSON en lugar de un redirect 302.
- **Errores uniformes** en `bootstrap/app.php`: 401 "No autenticado…", 403 "No tienes permiso…", 404 "El recurso solicitado no existe." / "Ruta no encontrada.", 405, 429 "Demasiadas solicitudes…". `redirectGuestsTo` devuelve `null` en `api/*` para no buscar la ruta `login` web.
- Contraseñas: estudiante mínimo 8 con letras y números; admin mínimo 10 con letras, números y símbolos. Se guardan con hash (cast `hashed`).
- `GET /admin/users` filtra en SQL con `when()` + `ilike` y pagina de a 12.
- Tokens sin expiración (`sanctum.expiration = null`) por ahora; se revocan con logout o `DELETE /me/tokens/{id}`. Pendiente evaluar expiración para producción.
- Sin `MustVerifyEmail` (pregunta abierta 3): requiere SMTP; queda documentado como riesgo (alguien podría registrar un correo @unab ajeno).

**Comandos:**
```bash
./vendor/bin/pest --filter="AuthTest|RolesTest"
php artisan route:list --path=api/v1
php artisan serve
```
**Archivos:** `app/Actions/Auth/*`, `app/Http/Controllers/Api/V1/{AuthController,TokenController}.php`, `app/Http/Controllers/Api/V1/Admin/UserController.php`, `app/Http/Requests/Concerns/ValidaCorreoInstitucional.php`, `app/Http/Requests/Auth/{RegisterRequest,LoginRequest,StoreTokenRequest}.php`, `app/Http/Requests/Admin/StoreAdminUserRequest.php`, `app/Http/Resources/UserResource.php`, `app/Http/Middleware/{EnsureRole,ForzarRespuestaJson}.php`, `app/Policies/{ProyectoPolicy,ComentarioPolicy}.php`, `app/Providers/AppServiceProvider.php`, `bootstrap/app.php`, `routes/api.php`, `tests/Feature/{AuthTest,RolesTest}.php`.
**Cómo probar (Postman, header `Accept: application/json`):**
1. `POST {{base_url}}/api/v1/register` body `{"name":"Ana Pérez","email":"Ana@UNAB.edu.co","password":"secreto123","password_confirmation":"secreto123"}` → **201**, `user.email = "ana@unab.edu.co"`, `user.role = "estudiante"`. Script de tests: `pm.environment.set("token", pm.response.json().token)`.
2. Mismo body con `"email":"ana@gmail.com"` → **422** `errors.email[0] = "Solo se permiten correos institucionales @unab.edu.co"`.
3. `POST /api/v1/login` `{"email":"admin@unab.edu.co","password":"Admin12345*"}` → **200** `{token, user}` (admin del seeder en local).
4. `GET /api/v1/me` con `Authorization: Bearer {{token}}` → **200**; sin token → **401**.
5. `GET /api/v1/admin/users` con token de estudiante → **403**; con token de admin → **200** paginado.
6. `POST /api/v1/logout` → **204**; repetir `/me` con el mismo token → **401**.
Pruebas automáticas: `./vendor/bin/pest` → 38 pruebas verdes.
**Commit:** `feat: implementar autenticación Sanctum con dominio institucional y roles`

---

## Paso 4 — API de proyectos: catálogos, publicaciones, likes, comentarios y moderación
**Qué se hizo:** los endpoints que necesitaba el frontend y que aún no existían, con sus Resources, FormRequests y pruebas.

**Endpoints nuevos:**
| Método | Ruta | Acceso | Respuesta |
|---|---|---|---|
| GET | `/api/v1/materias` · `/docentes` · `/tecnologias` | público | 200 `{data: [...]}` (sin paginar) |
| GET | `/api/v1/proyectos?q=&materia=&docente=&tecnologia=&semestre=&periodo=&orden=recientes\|populares&rango=diario\|semanal\|mensual\|anual\|historico&page=` | público | 200 paginado de 12 `{data, links, meta}` |
| GET | `/api/v1/proyectos/{slug}` | público | 200 `{data}` · 404 si no existe o fue eliminado |
| POST | `/api/v1/proyectos` (multipart si trae `portada`) | auth (`throttle:10,1`) | 201 `{data}` · 422 |
| DELETE | `/api/v1/proyectos/{slug}` | dueño o admin (`ProyectoPolicy`) | 204 · 403 |
| POST / DELETE | `/api/v1/proyectos/{slug}/like` | auth | 200 `{liked, likes_count}` |
| GET | `/api/v1/proyectos/{slug}/comentarios` | público | 200 paginado de 10 |
| POST | `/api/v1/proyectos/{slug}/comentarios` | auth (`throttle:20,1`) | 201 `{data}` · 422 |
| DELETE | `/api/v1/comentarios/{id}` | autor o admin (`ComentarioPolicy`) | 204 · 403 |
| GET | `/api/v1/me/proyectos` · `/me/likes` | auth | 200 paginado |
| GET / DELETE | `/api/v1/admin/proyectos` · `/admin/proyectos/{slug}` | `role:admin` | 200 paginado · 204 |

**Por qué:**
- **Rutas por `slug`** (`getRouteKeyName()`): URLs legibles (`/proyectos/campus-connect`) y no se expone el id secuencial.
- **`ProyectoResource`** devuelve `materia`, `docente`, `autor` (sin correo: `AutorResource`), `tecnologias`, `likes_count`, `comentarios_count`, `liked` (`null` si es anónimo) y `can.delete`. El frontend usa `can` solo para mostrar u ocultar botones; la API vuelve a validar con la Policy.
- **Lectura pública con token opcional:** las rutas GET están fuera de `auth:sanctum`, pero `$request->user('sanctum')` reconoce el token si llega, para calcular `liked` y `can`.
- **Sin N+1:** el scope `conDetalles()` carga relaciones, `withCount(['likes','comentarios'])` y `withExists('likes as liked_by_me')` en una sola consulta por relación.
- **Filtros en SQL** (scope `filtrar()`): `ilike` sobre título, contexto, autor y tecnologías (escapando `%` y `_`), `whereHas` para tecnología y semestre, orden por `likes_count` o fecha. `IndexProyectoRequest` valida la query string (422 si llega `orden=raro`).
- **Ranking por periodo (`rango`):** con `rango=diario|semanal|mensual|anual` se cuentan solo los likes con `likes.created_at` dentro del último día, semana, mes o año; `historico` cuenta todos los likes ("De todos los tiempos") (`withCount('likes as likes_rango_count')`) y se ordena por ese conteo; los empates se rompen por likes totales y luego por fecha. Tiene prioridad sobre `orden`. La hora de cada like (`likes.created_at`) la escribe `LikeController` con `now()` de Laravel (UTC), no el `CURRENT_TIMESTAMP` de PostgreSQL, para que guardado y ranking usen la misma zona horaria. Se inserta con `insertOrIgnore`: repetir el like no lo duplica ni mueve su hora; quitarlo y volver a darlo sí cuenta como un like nuevo. No excluye proyectos sin likes en el periodo: quedan al final, así la portada nunca aparece vacía.
- **Publicar** (`StoreProyectoRequest` + Action `PublicarProyecto`): el autor sale del token y el slug y la fecha los pone el sistema; si el body trae `user_id` o `slug`, se ignoran. Enlaces solo `https://`; periodo `AAAA-1|2`; de 1 a 10 tecnologías existentes y sin repetir. El proyecto y sus tecnologías se guardan en una transacción.
- **Likes idempotentes:** `POST` usa `syncWithoutDetaching` y `DELETE` usa `detach`. Repetirlos no cambia el conteo, gracias a la PK `(user_id, proyecto_id)`.
- **Datos de demostración** (`DemoSeeder`, solo con `APP_ENV=local`): 3 estudiantes (`sgomez`, `vtorres`, `dredondo` @unab.edu.co, contraseña `Estudiante123`) y 6 proyectos. Asigna el slug a mano porque `DatabaseSeeder` usa `WithoutModelEvents`.

**Archivos:** `app/Http/Controllers/Api/V1/{CatalogoController,ProyectoController,LikeController,ComentarioController,MiEspacioController}.php`, `app/Http/Controllers/Api/V1/Admin/ProyectoController.php`, `app/Http/Requests/Proyectos/*`, `app/Http/Resources/{Proyecto,Comentario,Materia,Docente,Tecnologia,Autor}Resource.php`, `app/Actions/Proyectos/PublicarProyecto.php`, `app/Models/Proyecto.php`, `database/seeders/DemoSeeder.php`, `routes/api.php`, `tests/Feature/ProyectosApiTest.php`.
**Pruebas:** `./vendor/bin/pest --testsuite=Feature` → 63 pruebas verdes (25 nuevas).
**Commits:** `feat(api): catálogos, proyectos, likes, comentarios, mi espacio y moderación` · `feat(db): datos de demostración para entorno local`

---

## Paso 5 — Integración del frontend (Lovable) dentro de Laravel
**Qué se hizo:** la interfaz exportada de Lovable ("Screen Faithful Sitecraft") se movió a `resources/js` y Laravel la sirve como SPA. Todos los datos simulados (`projects.ts`, `useDemo()`) se reemplazaron por llamadas reales a `/api/v1`. La carpeta `frontend/` ya no existe.

**Arquitectura:**
```
resources/js/
  app.tsx                 ← monta React, QueryClient y TanStack Router
  routes/                 ← rutas por archivo (TanStack Router genera routeTree.gen.ts)
    __root.tsx  index.tsx  proyectos.$id.tsx  publicar.tsx  mi-espacio.tsx  administracion.tsx
  components/             ← ProjectShell (layout + modal de acceso), ProjectCard, Estados, ui/
  lib/api/client.ts       ← fetch + Bearer + ApiError + mensajes en español
  lib/api/tipos.ts        ← interfaces copiadas de los API Resources
  lib/api/servicios.ts    ← un método por endpoint
  lib/auth.tsx            ← AuthProvider / useAuth()
  lib/consultas.ts        ← catálogos cacheados y like optimista
  lib/formato.ts          ← fechas, portadas, video embebido
  assets/                 ← 3 portadas ilustrativas
resources/views/app.blade.php  ← HTML base con @vite
routes/web.php                 ← cualquier ruta que no sea api/ devuelve la SPA
```

**Por qué:**
- **Una sola app en vez de dos.** Se pidió que no quedara la carpeta `frontend/`. Por eso se quitaron TanStack Start (SSR), Nitro y la configuración y telemetría de Lovable, y se dejó una SPA compilada por el Vite de Laravel (`laravel-vite-plugin` + `@vitejs/plugin-react` + `@tanstack/router-plugin`). Con `php artisan serve` basta.
- **Mismo origen, sin CORS.** La SPA y la API están en `http://localhost:8000`, así que el navegador no hace preflight. `config/cors.php` se mantiene limitado a `FRONTEND_URL` para clientes externos (otro frontend o herramientas), nunca con `*`.
- **Token Bearer de Sanctum en `localStorage`** (clave `unab_token`), igual que lo usaría un cliente externo o el futuro MCP. `client.ts` agrega `Authorization`, convierte errores en `ApiError {status, message, errors}` y, ante un 401 con sesión, borra el token y emite `unab:sesion-expirada`. `AuthProvider` escucha ese evento y cierra la sesión.
- **`routes/web.php`** usa `Route::view('/{any?}', 'app')` excluyendo `api/`, `up`, `build/` y `storage/`. Así un F5 en `/proyectos/campus-connect` funciona y los 404 de la API siguen siendo JSON.
- **TanStack Query:** `useQuery` para leer y `useMutation` + `invalidateQueries` para escribir. Los catálogos tienen `staleTime: Infinity`. No se reintentan errores 4xx.
- **Like optimista:** se actualizan al instante todas las listas y el detalle (`['proyectos', …]`). Luego se usa el `{liked, likes_count}` del servidor y, si falla, se revierte.
- **Catálogo:** los filtros van en la URL (`validateSearch`: `q`, `materia`, `docente`, `tecnologia`, `semestre`, `rango`, `page`, `vista`). La búsqueda espera 400 ms (debounce). El semestre es un select más dentro del panel de filtros, junto a materia, docente y tecnología. Las estadísticas usan `meta.total` y el tamaño de los catálogos.
- **Portada = proyectos más valorados:** siempre pide `rango` a la API, con botones Diario / Semanal / Mensual / Anual / De todos los tiempos (por defecto semanal, que no se escribe en la URL). Se quitó el selector "Más recientes / Más populares".
- **Filtrar sin saltar arriba:** cada filtro cambia la URL con `navigate`, y TanStack Router por defecto lleva el scroll al inicio en cada navegación. En el catálogo se pasa `resetScroll: false`, y con `keepPreviousData` la lista se reemplaza en el sitio. Al cambiar de página se hace scroll suave al inicio del listado (`#ranking`), no al tope de la página.
- **Detalle:** video incrustado solo de YouTube (`youtube-nocookie.com`) o Google Drive; cualquier otro enlace se muestra como link. Los comentarios se paginan con "Ver más". Los botones de borrar aparecen según `can.delete`.
- **Publicar:** los campos y reglas son los de `StoreProyectoRequest`: selects de catálogo, tecnologías múltiples (máximo 10), periodo `AAAA-1|2` y video opcional. No se piden autor ni fecha. Los errores 422 aparecen debajo de cada campo.
- **Registro:** pide lo que exige `RegisterRequest` (nombre, correo, contraseña y confirmación; programa y semestre son opcionales). El correo se normaliza (trim + minúsculas) y el dominio se valida también en el cliente. Se quitó el selector de rol de demostración.
- **Foto de portada:** al publicar se puede subir una foto (opcional). Se envía como `multipart/form-data` (`formularioProyecto()` en `servicios.ts`; `client.ts` no fija `Content-Type` cuando el body es `FormData`, para que el navegador ponga el `boundary`). Hay vista previa y se puede cambiar o quitar antes de publicar. Las tarjetas y el detalle usan `portada_url`; si es `null`, muestran una de las 3 ilustraciones elegida de forma estable por `id`.
  - **Backend:** columna `proyectos.portada_path` (nullable). `StoreProyectoRequest` exige `image` + `mimes:jpg,jpeg,png,webp` (sin SVG, que puede llevar scripts), máximo 2 MB (el `upload_max_filesize` por defecto de PHP) y al menos 300 × 150 px. `PublicarProyecto` la guarda con `store('portadas', 'public')`: nombre aleatorio y extensión según el contenido, no según lo que mande el usuario. Si falla la transacción, borra el archivo. `ProyectoResource` expone `portada_url` (URL pública del disco `public`).
  - **Pendiente:** al ser *soft delete*, borrar un proyecto no borra su foto. No se redimensiona en el servidor porque la extensión GD no está instalada.
  - **Requisito:** `php artisan storage:link` (crea `public/storage`, ya ignorado en git). Sin ese enlace, las fotos dan 404.
- **"Conecta con tu IA":** en este paso el servidor MCP aún no existía y la sección solo copiaba el contexto. En el Paso 6 se reemplazó por el botón "Conéctate por MCP".

**Comandos:**
```bash
npm install
npm run build        # o `npm run dev` para desarrollo con recarga en caliente (junto a php artisan serve)
npm test             # Vitest: cliente API, dominio, video y rutas
npm run typecheck    # tsc --noEmit
php artisan storage:link   # una vez: sirve las fotos de portada en /storage
php artisan serve    # http://localhost:8000
```
**Verificación hecha** (navegador headless y peticiones HTTP contra el servidor real):
1. Registro con `@gmail.com` → 422 "Solo se permiten correos institucionales @unab.edu.co".
2. Registro con `@unab.edu.co` → 201, rol `estudiante`, correo en minúsculas.
3. Publicar → aparece primero en el catálogo con la fecha del sistema (`publicado_en`).
4. Like → `{"liked":true,"likes_count":1}`; el comentario se publica.
5. Un estudiante recibe `can.delete=false` en proyectos ajenos y un `DELETE` responde 403.
6. El admin recibe `can.delete=true` y `DELETE /admin/proyectos/{slug}` responde 204 (después el proyecto da 404).
`npm test` → 15 pruebas verdes · `npx tsc --noEmit` sin errores · `pest` → 63 verdes.

**Nota de Windows:** al probar con `curl` o PowerShell 5.1, los acentos escritos directamente en `-d` no se envían en UTF-8 y Laravel recibe el JSON vacío (422 con "obligatorio" en todo). Usa `--data-binary @archivo.json` o escapes `ó`. El navegador siempre envía UTF-8.

**Commits:** `feat(front): integrar el frontend de Lovable en Laravel como SPA` · `feat(front): cliente API, tipos y servicios` · `feat(front): login, registro y sesión con Sanctum` · `feat(front): catálogo conectado a la API` · `feat(front): detalle, likes y comentarios` · `feat(front): publicar proyecto` · `feat(front): perfil con publicaciones y me gusta` · `feat(front): panel de administración` · `docs: integración del frontend con la API`

---

## Paso 6 — Servidor MCP: publicar desde Claude
**Qué se hizo:** un servidor MCP (Model Context Protocol) en `POST /mcp/proyectos` con el paquete oficial `laravel/mcp`, y el botón **"Conéctate por MCP"** en la web. El estudiante conecta Claude una vez y luego publica con un mensaje: solo da el **enlace del repositorio** y el **nombre del docente**. El **publicante** es la cuenta dueña del token. Claude lee el repositorio y completa el título, la descripción, la materia, las tecnologías y la foto.

**Herramientas MCP** (`backend/app/Mcp/Tools`):
| Herramienta | Qué hace |
|---|---|
| `listar_catalogos` | Devuelve la cuenta conectada (publicante) y las materias, docentes y tecnologías válidas. Solo lectura. |
| `leer_repositorio` | Lee un repositorio público de GitHub con su API: descripción, temas, lenguajes, README (recortado a 12 000 caracteres) y la portada sugerida (`opengraph.githubassets.com`). Solo lectura. |
| `publicar_proyecto` | Publica a nombre del dueño del token. Recibe **nombres** (docente, materia, tecnologías) y los traduce a ids. Devuelve el enlace. |

**Cómo conectarse** (lo muestra el botón, con la llave ya incluida):
```bash
# Claude Code
claude mcp add --transport http proyectos-unab http://localhost:8000/mcp/proyectos --header "Authorization: Bearer <llave>"
```
```json
// Claude Desktop (claude_desktop_config.json, requiere Node.js)
{ "mcpServers": { "proyectos-unab": { "command": "npx",
  "args": ["-y", "mcp-remote", "http://localhost:8000/mcp/proyectos", "--header", "Authorization:${AUTH_HEADER}"],
  "env": { "AUTH_HEADER": "Bearer <llave>" } } } }
```
Prompt de ejemplo: *"Publica mi proyecto en Foldex. Repositorio: https://github.com/usuario/mi-proyecto. Docente: Fabián Suárez"*.

**Por qué:**
- **Mismo token de Sanctum que la web.** El botón llama a `POST /api/v1/me/tokens` con el nombre `Claude (MCP)`. La ruta MCP usa `auth:sanctum`, así que el autor siempre sale del token y nunca de lo que escriba Claude. Desde el mismo diálogo se revocan las conexiones (`DELETE /me/tokens/{id}`).
- **Mismas reglas que el formulario.** `publicar_proyecto` valida con las reglas de `StoreProyectoRequest` y guarda con la Action `PublicarProyecto`. Lo único nuevo es `ResolverCatalogos`: compara nombres sin tildes ni mayúsculas ("fabian suarez" → "Fabián Enrique Suárez Carvajal"). Si hay cero o varias coincidencias, responde con las opciones válidas para que Claude pregunte (no adivina). Las tecnologías que no están en el catálogo se omiten y se avisan. Si falta el periodo, se usa el actual (enero–junio → `-1`, julio–diciembre → `-2`).
- **Foto de portada.** Claude manda `portada_url`. Si no la manda y el repositorio es de GitHub, se usa su imagen social. `DescargarPortada` solo acepta `https`, exige que el host resuelva a una **IP pública** (para que nadie use el servidor para consultar la red interna: SSRF), y limita a 2 MB y 10 s. La imagen pasa por las mismas reglas de `portada`. Si falla, el proyecto se publica sin foto y se avisa.
- **Límites:** `throttle:60,1` para mensajes MCP y 10 publicaciones por minuto por usuario (igual que la web).
- **¿Por qué no se conecta desde claude.ai (web)?** Los conectores de claude.ai necesitan una URL pública con HTTPS y OAuth. En local (`localhost`) solo se puede con Claude Code o Claude Desktop. Al desplegar en Azure se puede agregar OAuth (laravel/mcp lo soporta con Passport).
- `routes/ai.php` se registra desde `bootstrap/app.php` y la ruta comodín de la SPA excluye `mcp/`. Los errores 401/403/404 de `/mcp` también salen en JSON y en español.
- `GITHUB_TOKEN` (opcional, en `.env`) sube el límite de la API de GitHub de 60 a 5000 consultas por hora.

**Archivos:** `backend/app/Mcp/Servers/ProyectosServer.php`, `backend/app/Mcp/Tools/{ListarCatalogos,LeerRepositorio,PublicarProyecto}.php`, `backend/app/Actions/Proyectos/{ResolverCatalogos,DescargarPortada}.php`, `backend/routes/ai.php`, `backend/config/services.php`, `frontend/js/components/ConectarMcp.tsx`, `tests/Feature/McpTest.php`, `frontend/js/test/mcp.test.ts`.
**Cómo probar:** `./vendor/bin/pest --filter=Mcp` (16 pruebas: token obligatorio, publicar resolviendo nombres, portada, SSRF, docente inexistente y lectura de GitHub simulada). Prueba manual con el servidor real: `initialize` → 200, `listar_catalogos` devuelve la cuenta conectada y sin token → 401.
**Commits:** `build: instalar laravel/mcp para el servidor MCP` · `feat(mcp): servidor MCP para publicar proyectos desde Claude` · `feat(front): botón Conéctate por MCP y edición de proyectos para el admin`

---

## Paso 7 — Perfiles: admin edita y borra todo; estudiante publica y borra lo suyo
**Qué se hizo:**
| Acción | Estudiante | Admin |
|---|---|---|
| Publicar (web o MCP) | ✔ | ✔ |
| Borrar su propio proyecto | ✔ | ✔ |
| Borrar proyectos ajenos | ✘ (403) | ✔ |
| Editar cualquier proyecto | ✘ (403) | ✔ `PUT /api/v1/proyectos/{slug}` |
| Panel `/administracion` | ✘ | ✔ |

- `ProyectoPolicy::update` ahora es **solo admin**. Antes también lo permitía al dueño, aunque no había endpoint de edición. `ProyectoResource` expone `can.update` y el frontend muestra **Editar** solo si viene en `true`.
- Endpoint nuevo `PUT /api/v1/proyectos/{slug}` (`UpdateProyectoRequest` + Action `ActualizarProyecto`): tiene las mismas reglas que publicar, más `quitar_portada`. No cambia el autor, el slug (los enlaces compartidos siguen funcionando) ni la fecha. Si llega una foto nueva, borra la anterior **después** de guardar en la base de datos. PHP no lee `multipart/form-data` en `PUT`, por eso, con foto, el frontend envía `POST` con `_method=PUT` (Laravel lo trata como `PUT`).
- Pantalla `/editar/$id` (solo admin) que reutiliza el formulario de publicar (`FormularioProyecto.tsx`, extraído de `publicar.tsx`). El botón **Editar** aparece en el detalle y en el panel de administración.
- **Admin por defecto: `fabian@unab.edu.co`** (`ADMIN_EMAIL`, nombre en `ADMIN_NAME`). En local, si `ADMIN_PASSWORD` está vacío, la contraseña es `Admin12345*`. Se aplica con `php artisan db:seed --class=AdminSeeder`. El seeder no le quita el rol a admins anteriores: en la BD local `admin@unab.edu.co` sigue siendo admin hasta que se elimine desde el panel.
- El registro de estudiantes no cambia: solo correos `@unab.edu.co` (`ALLOWED_EMAIL_DOMAIN`) y siempre con rol `estudiante`.

**Pruebas:** policy (estudiante no edita y admin sí), `PUT` del admin sin cambiar autor ni slug, 403 al autor, reemplazo y eliminación de portada, y el seeder crea a fabian como admin.
**Commit:** `feat(api): solo el admin edita proyectos; fabian@unab.edu.co es el admin por defecto`

---

## Paso 8 — Carrusel de fotos en la portada
**Qué se hizo:** la foto grande del inicio ahora es un carrusel (`frontend/js/components/CarruselHero.tsx`) con las fotos de **`frontend/contexto_img/`**.
- **Las fotos se leen en vivo, sin recompilar.** Basta con copiar o borrar archivos `.jpg/.jpeg/.png/.webp/.avif` en esa carpeta: al recargar la página (o en máximo 1 minuto si ya está abierta) aparece la foto con su propio punto de navegación. El orden es por nombre de archivo (`01-…`, `02-…` para elegirlo). Si la carpeta queda vacía, se usa la ilustración del campus.
- Backend: `CarruselController` → `GET /api/v1/carrusel` lista las imágenes de `config('carrusel.ruta')` (`backend/config/carrusel.php`, cambiable con `CARRUSEL_RUTA`) y `GET /api/v1/carrusel/{archivo}` las sirve. Solo entrega archivos que estén en esa lista (nadie puede pedir `../../.env`) y no acepta SVG. La URL lleva `?v=<fecha del archivo>` para que el navegador no muestre una versión vieja si se reemplaza una foto con el mismo nombre.
- Frontend: `CarruselHero` pide la lista con React Query (`refetchInterval` de 60 s). Antes se usaba `import.meta.glob`, que solo veía las fotos al compilar.
- Cambia de foto cada 6 s con transición suave. Se pausa con el mouse encima o con el foco del teclado, y no avanza solo si el sistema pide "reducir movimiento". Las flechas y los puntos van abajo, en el centro, para no tapar el texto de bienvenida.
- Las fotos actuales son verticales y el banner es horizontal, así que se recortan (centradas al 40 % para dejar ver las caras). Conviene usar fotos horizontales (≈ 1600 × 600 o más).
**Commits:** `feat(front): carrusel de fotos de contexto_img en la portada` · `feat: el carrusel lee contexto_img en vivo, sin recompilar`

---

## Paso 9 — Carpetas `backend/` y `frontend/` y comentarios en cada archivo
**Qué se hizo:** se separó el proyecto en `backend/` (app, config, database, lang, routes) y `frontend/` (js, css, views, contexto_img) **sin dividirlo en dos aplicaciones**: sigue siendo un solo Laravel que sirve la SPA y la API desde el mismo origen. Además, cada archivo tiene comentarios en español: para qué sirve el archivo, qué se importa, cada sección o componente y, en los controladores, la ruta HTTP que atiende cada método (`// Ruta: GET /api/v1/proyectos`).

**Cómo se hizo sin romper nada:**
- `bootstrap/app.php`: las rutas apuntan a `backend/routes/*` y al final se llama `useAppPath`, `useConfigPath`, `useDatabasePath` y `useLangPath` con las carpetas de `backend/`.
- `composer.json`: `App\` → `backend/app/`, `Database\Factories\` y `Database\Seeders\` → `backend/database/...` (`composer dump-autoload`). Así `php artisan make:*` también crea los archivos en `backend/`.
- `backend/config/view.php`: las vistas Blade se buscan en `frontend/views`.
- `vite.config.js`, `vitest.config.ts` y `tsconfig.json`: entradas, alias `@` y rutas de TanStack Router apuntan a `frontend/js`. `@source "../js"` de Tailwind no cambió porque la ruta relativa es la misma.
- Se quedan en la raíz: `bootstrap/`, `public/`, `storage/`, `vendor/`, `node_modules/` (los exigen Laravel o npm) y `tests/` (Pest busca `tests/Pest.php` en la raíz; moverlo obligaría a usar `--test-directory` en cada comando).
- Los comandos de siempre no cambian: `php artisan serve`, `npm run dev/build`, `./vendor/bin/pest --testsuite=Feature`, `npm test`.

**Verificación:** `pest` 92 verdes · `npm test` 25 verdes · `npm run typecheck` sin errores · `npm run build` correcto · `php artisan route:list` muestra `/api/v1/*`, `/mcp/proyectos` y la SPA.
**Commits:** `refactor: separar el proyecto en backend/ y frontend/` · `docs(back): comentar cada archivo del backend…` · `docs(front): comentar cada archivo del frontend…`

---

## Paso 10 — Nombre Foldex y pulido visual (sin cambiar funciones)
**Qué se hizo:** la página se llama **Foldex**. Se cambiaron el logo, el título de la portada, los títulos de pestaña (`… | Foldex`), `APP_NAME`, el nombre del servidor MCP y el prompt de ejemplo. Además se hizo una pasada de pulido con el skill *Impeccable* para quitar marcas típicas de diseño generado por IA, conservando colores, tipografía, distribución y comportamiento.
- Sin etiquetas decorativas en mayúsculas sobre los títulos ("LA BIBLIOTECA DE NUESTRA COMUNIDAD", "MODERACIÓN"…). En el detalle, materia · semestre · periodo pasó a ser una línea de datos debajo del título.
- Se cambiaron textos de relleno por texto concreto. Se fueron: "Ideas de aquí. Impacto en todas partes.", "COMUNIDAD · INGENIERÍA · INNOVACIÓN", "Hecho para compartir conocimiento" y "Lo que creas merece ser compartido".
- Las cifras del catálogo van en una sola línea, sin iconos ni ceros de relleno ("09") y con números tabulares.
- Se quitaron la franja de color a la izquierda de la invitación final, la flecha flotante de las tarjetas y las flechas "↗" en enlaces internos.
- La selección de texto, el cursor, el foco de los enlaces y la barra de desplazamiento usan los colores del sitio.

**Verificación:** `pest` 99 verdes · `npm test` 26 verdes · typecheck y build sin errores · detector de Impeccable sin hallazgos · capturas en escritorio y celular.
**Commit:** `feat(front): nombre Foldex y pulido visual sin texto de relleno`

---

## Paso 11 — Despliegue en Azure (App Service + PostgreSQL)
**Qué se hizo:** el proyecto se publicó en **https://foldex-unab.azurewebsites.net** sin cambiar tecnologías: el mismo Laravel 13 sirve la API y la SPA de React compilada con Vite.

| Recurso (grupo `Foldex`, región `mexicocentral`) | Para qué |
|---|---|
| App Service Plan `foldex-plan` (Linux, B1) + Web App `foldex-unab` (PHP 8.4, nginx + php-fpm) | Laravel + frontend compilado |
| Azure Database for PostgreSQL Flexible `foldex-db-jbog8` (PG 17, B1ms), BD `foldex` | La base de datos (SSL obligatorio, `DB_SSLMODE=require`) |

Archivos nuevos en `deploy/azure/`:
- `startup.sh` — *Startup Command* de la Web App. En cada arranque copia `nginx.conf`, crea las carpetas de `storage/`, genera las cachés (`config`, `route`, `view`), corre `migrate --force` y `db:seed --force` (los seeders son idempotentes: catálogos y admin; los datos de demo solo se cargan en local).
- `nginx.conf` — raíz en `public/`, todo lo que no es archivo va a `index.php`, `/storage/` sirve las portadas desde `storage/app/public` (sin depender del enlace simbólico), caché larga para `/build/` y bloqueo de archivos ocultos.
- `php/foldex.ini` — límites de subida (portadas de 2 MB) y OPcache; se carga con la App Setting `PHP_INI_SCAN_DIR`.
- `desplegar.ps1` — empaqueta el **último commit** (`git archive`), hace `composer install --no-dev`, `npm ci` + `npm run build`, arma el zip y lo sube con `az webapp deploy`. No toca el `vendor/` ni el `node_modules/` locales ni sube el `.env`.

Cambio de código: `bootstrap/app.php` confía en el proxy (`trustProxies(at: '*')`), porque en Azure el HTTPS termina en el balanceador; sin esto Laravel generaría URLs `http://` para los assets y las portadas (contenido mixto).

Configuración: no hay `.env` en el servidor; todo va en **App Settings** de la Web App (`APP_ENV=production`, `APP_DEBUG=false`, `APP_KEY`, `DB_*`, `ADMIN_PASSWORD`, `LOG_CHANNEL=stderr`, `SESSION_SECURE_COOKIE=true`…). Se ven en el portal → Web App → *Configuración* o con `az webapp config appsettings list -g Foldex -n foldex-unab`.

**Volver a desplegar:** hacer commit y ejecutar `.\deploy\azure\desplegar.ps1` (requiere `az login`). Logs en vivo: `az webapp log tail -g Foldex -n foldex-unab`.
**Commit:** `feat(deploy): despliegue en Azure App Service con PostgreSQL Flexible`

---

## Preguntas de sustentación

### Básicas
**¿Qué es una API REST y un endpoint?**
Una API REST expone recursos (proyectos, comentarios) por HTTP usando URLs y métodos con significado; responde JSON. Un endpoint es la combinación método + URL, p. ej. `GET /api/v1/proyectos`.

**¿Para qué sirve `.env`?**
Guarda la configuración que cambia por entorno (credenciales de BD, APP_KEY, APP_DEBUG). No se sube a Git porque contiene secretos; se versiona `.env.example` como plantilla.

**¿Qué es Docker: imagen vs contenedor?**
Una imagen es una plantilla inmutable (SO + dependencias, p. ej. `postgres:17-alpine`); un contenedor es una instancia en ejecución de esa imagen. Aquí PostgreSQL corre en un contenedor con un volumen (`pgdata`) para persistir datos.

**¿Qué es una migración?**
Un archivo PHP versionado que describe cambios al esquema (crear tabla, agregar columna) con `up()` y `down()`. Permite que todo el equipo y Azure tengan la misma estructura con `php artisan migrate`.

**¿Qué es Eloquent?**
El ORM de Laravel: cada tabla se representa con un modelo (`Proyecto`) y las relaciones (`belongsTo`, `hasMany`, `belongsToMany`) se navegan como propiedades (`$proyecto->user->email`).

**¿Qué significa cada código (200/201/204/401/403/404/422/429)?**
200 OK (lectura/login), 201 recurso creado (registro), 204 éxito sin cuerpo (logout/borrar), 401 no autenticado (falta o es inválido el token), 403 autenticado pero sin permiso, 404 no existe, 422 datos inválidos, 429 demasiados intentos.

**¿Para qué sirve un FormRequest?**
Saca la validación del controlador a una clase (`RegisterRequest`) con `rules()`, `messages()` y `prepareForValidation()`. Si falla, Laravel responde 422 automáticamente y el controlador solo recibe datos válidos con `$request->validated()`.

**¿Qué es un middleware?**
Un filtro que se ejecuta antes/después de la petición. `auth:sanctum` exige token válido, `role:admin` exige rol, `throttle:login` limita intentos y `ForzarRespuestaJson` fija el header `Accept`.

### Avanzadas
**¿Cómo se diferencia un usuario autenticado de uno anónimo en una API?**
Por el header `Authorization: Bearer <token>`. Sanctum busca el hash del token en `personal_access_tokens` y carga al usuario; sin token (o revocado) `$request->user()` es `null` y las rutas con `auth:sanctum` responden 401.

**¿Por qué validar el dominio en registro y login, y por qué `ends_with` con la `@`?**
En registro evita crear cuentas externas; en login bloquea cuentas que pudieron entrar por otro camino (BD, error previo). Con la `@` incluida, `x@unab.edu.co.fake.com` o `x@falsounab.edu.co` no pasan; normalizar a minúsculas evita que `UNAB.EDU.CO` lo esquive.

**¿Policy vs middleware?**
El middleware decide por ruta/grupo sin mirar el registro (¿es admin?). La Policy decide sobre un modelo concreto (¿este usuario es dueño de *este* proyecto?). Se usan juntos: `role:admin` para `/admin`, `ProyectoPolicy` para editar/borrar.

**¿Por qué el login responde el mismo error si el correo no existe o la contraseña falla?**
Para no permitir enumeración de cuentas: un atacante no puede saber qué correos están registrados. Además el throttle (5/min por correo+IP) frena la fuerza bruta.

**¿Riesgos del token en `localStorage`?**
Cualquier script inyectado (XSS) puede leerlo y robar la sesión. Mitigación: sanitizar contenido (comentarios), CSP, tokens revocables (`/me/tokens`, logout) y, a futuro, expiración del token.

**¿Por qué el registro nunca crea admins?**
Porque es público: si aceptara `role`, cualquiera se haría admin. `role` no es fillable y la Action asigna `estudiante` explícitamente; los admins solo nacen del seeder o de otro admin.

**¿Qué problema tiene un contador global de likes?**
No sabe quién dio like (se puede dar infinitos) y se desincroniza con concurrencia. La tabla pivote `likes` con PK compuesta `(user_id, proyecto_id)` garantiza 1 like por usuario y el total se obtiene con `withCount('likes')`.

**¿Por qué `user_id`/`role` no son fillable?**
Con asignación masiva (`create($request->validated())`) un atacante podría enviar `"role":"admin"` o `"user_id":5`. Al excluirlos, se ignoran y se asignan solo desde el servidor (`$request->user()`, seeder o admin).

**¿Por qué softDeletes?**
El borrado lógico marca `deleted_at` en vez de eliminar la fila: el admin puede moderar y aún queda evidencia/auditoría, y se puede restaurar. Las consultas normales excluyen esos registros automáticamente.

**¿Por qué `restrictOnDelete` en materias/docentes y `cascadeOnDelete` desde usuarios?**
Un catálogo en uso no debe desaparecer (dejaría proyectos huérfanos); en cambio, los proyectos, comentarios y likes pertenecen al usuario y no tienen sentido sin él (decisión pendiente de confirmar).

**¿Por qué versionar la API (`/api/v1`)?**
Permite cambiar el contrato en una `v2` sin romper clientes existentes (frontend Lovable, servidor MCP). Los clientes migran a su ritmo.

**¿Por qué CORS con frontend en otro dominio?**
El navegador bloquea peticiones `fetch` a un origen distinto salvo que el servidor lo autorice con cabeceras CORS. Se autoriza solo `FRONTEND_URL` (no `*`) para que otros sitios no puedan usar la API desde el navegador de un usuario.

**¿Por qué las pruebas usan PostgreSQL y no SQLite en memoria?**
SQLite es más rápido pero no soporta igual `ilike`, tipos ni restricciones; probar con el mismo motor de producción evita falsos positivos.

### Integración del frontend
**¿Qué es CORS y por qué aquí no hace falta para la SPA?**
Es la regla del navegador que bloquea `fetch` hacia otro origen (esquema + dominio + puerto) salvo que el servidor lo permita con cabeceras `Access-Control-Allow-*`. Como Laravel sirve la SPA y la API desde el mismo origen (`localhost:8000`), no hay petición cruzada. `config/cors.php` queda para clientes externos y solo admite los orígenes de `FRONTEND_URL`, nunca `*`. CORS no protege la API de `curl` o Postman: solo controla qué páginas web pueden leer la respuesta desde el navegador de un usuario.

**¿Token Bearer o cookies de sesión?**
Con cookies (Sanctum SPA), el navegador envía la sesión sola. Eso exige CSRF y `supports_credentials`, y solo sirve para el mismo dominio o subdominios. Con Bearer, el cliente envía el token de forma explícita: funciona igual para la SPA, Postman, apps móviles y el futuro MCP, y no hay CSRF porque nada se envía automáticamente. A cambio, el token queda al alcance de JavaScript.

**¿Qué riesgos tiene guardar el token en `localStorage`?**
Cualquier script que logre ejecutarse en la página (XSS, dependencia comprometida) puede leerlo y robar la sesión hasta que se revoque. Mitigaciones aplicadas: React escapa todo el texto (no se usa `dangerouslySetInnerHTML`), los videos solo se incrustan desde YouTube/Drive validando el ID, los enlaces externos llevan `rel="noopener noreferrer"`, ante un 401 se borra el token y existen `logout` y `DELETE /me/tokens/{id}`. Pendientes: CSP y expiración de tokens.

**¿Por qué una variable `VITE_*` no puede guardar secretos?**
Vite reemplaza `import.meta.env.VITE_*` por su valor al compilar, y ese valor queda escrito en el JavaScript que descarga cualquier visitante. Solo sirve para configuración pública, como `VITE_API_URL`. Las claves privadas viven en el `.env` de Laravel, que nunca llega al navegador.

**¿Por qué el frontend esconde botones con `can.delete` si la API ya valida?**
Es solo experiencia de usuario: evita mostrar acciones que van a fallar. La seguridad está en el servidor (`Gate::authorize` con la Policy y `role:admin`), porque cualquiera puede llamar a la API saltándose la interfaz.

**¿Qué es una actualización optimista y cuándo conviene?**
Es cambiar la interfaz antes de que responda el servidor (el corazón se pinta al instante) y corregir con la respuesta real, o revertir si hay error. Conviene en acciones frecuentes, baratas y casi siempre exitosas, como un like. No conviene en operaciones críticas como publicar o borrar.

### MCP y perfiles
**¿Qué es MCP?**
Model Context Protocol: un estándar para que un asistente de IA (Claude) use herramientas de una aplicación. El servidor declara herramientas con nombre, descripción y esquema JSON de entrada, y Claude decide cuándo llamarlas. Aquí se habla JSON-RPC por HTTP en `POST /mcp/proyectos`.

**¿Cómo se sabe quién publica desde Claude?**
Por el token Bearer de Sanctum con el que se configuró la conexión: `auth:sanctum` carga al usuario y `publicar_proyecto` usa `$request->user()` como autor. Lo que escriba Claude nunca decide el autor. Revocar el token corta la conexión.

**¿Por qué el servidor no descarga cualquier URL de imagen?**
Porque una URL controlada por un atacante podría apuntar a la red interna del servidor (SSRF: `127.0.0.1`, `169.254.169.254` en la nube…). Se exige `https`, IP pública, tamaño máximo y tiempo límite, y luego se valida que de verdad sea una imagen.

**¿Por qué editar es solo del admin y no del dueño?**
Es una decisión del equipo: el estudiante publica y puede borrar lo suyo; corregir publicaciones ya visibles es moderación. La regla vive en `ProyectoPolicy::update`, así que la API responde 403 aunque alguien llame al endpoint sin pasar por la interfaz.
