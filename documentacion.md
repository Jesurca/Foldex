# Documentación técnica — Repositorio de Proyectos UNAB (API Laravel)

Bitácora de cada paso del backend: qué se hizo, por qué, cómo probarlo y el commit asociado.

## Estructura de carpetas relevante

```
app/
  Actions/Auth/            ← lógica de negocio reutilizable (API y MCP)
  Enums/Role.php           ← roles estudiante/admin
  Http/Controllers/Api/V1/ ← controladores versionados
  Http/Middleware/EnsureRole.php
  Http/Requests/           ← FormRequests (validación)
  Http/Resources/          ← API Resources (contrato JSON)
  Models/                  ← User, Materia, Docente, Tecnologia, Proyecto, Comentario
config/auth.php            ← allowed_domain (ALLOWED_EMAIL_DOMAIN)
config/cors.php            ← orígenes desde FRONTEND_URL
database/migrations/ factories/ seeders/
lang/es/                   ← mensajes de validación en español
resources/js/              ← interfaz React (SPA) que consume /api/v1 — ver Paso 5
resources/views/app.blade.php ← HTML base de la SPA
routes/api.php             ← /api/v1/...
routes/web.php             ← cualquier otra ruta devuelve la SPA
tests/Feature/             ← pruebas Pest contra PostgreSQL
docker-compose.yml         ← PostgreSQL local
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
**Cómo probar:** `GET {{base_url}}/api/v1/ping` con `Accept: application/json` → **200** `{"status":"ok","app":"Repositorio Proyectos UNAB","version":"v1"}`.
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
| GET | `/api/v1/proyectos?q=&materia=&docente=&tecnologia=&semestre=&periodo=&orden=recientes\|populares&page=` | público | 200 paginado de 12 `{data, links, meta}` |
| GET | `/api/v1/proyectos/{slug}` | público | 200 `{data}` · 404 si no existe o fue eliminado |
| POST | `/api/v1/proyectos` | auth (`throttle:10,1`) | 201 `{data}` · 422 |
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
- **Catálogo:** los filtros van en la URL (`validateSearch`: `q`, `materia`, `docente`, `tecnologia`, `semestre`, `orden`, `page`, `vista`). La búsqueda espera 400 ms (debounce). Los chips de categorías fijas se cambiaron por los semestres de las materias. Las estadísticas usan `meta.total` y el tamaño de los catálogos.
- **Detalle:** video incrustado solo de YouTube (`youtube-nocookie.com`) o Google Drive; cualquier otro enlace se muestra como link. Los comentarios se paginan con "Ver más". Los botones de borrar aparecen según `can.delete`.
- **Publicar:** los campos y reglas son los de `StoreProyectoRequest`: selects de catálogo, tecnologías múltiples (máximo 10), periodo `AAAA-1|2` y video opcional. No se piden autor ni fecha. Los errores 422 aparecen debajo de cada campo.
- **Registro:** pide lo que exige `RegisterRequest` (nombre, correo, contraseña y confirmación; programa y semestre son opcionales). El correo se normaliza (trim + minúsculas) y el dominio se valida también en el cliente. Se quitó el selector de rol de demostración.
- **Imágenes:** el backend no guarda imágenes, así que la portada es una de 3 ilustraciones elegida de forma estable por `id`.
- **"Conecta con tu IA":** el servidor MCP aún no existe en el backend. La sección se dejó como estaba: copia el contexto público del proyecto e indica "Próximamente".

**Comandos:**
```bash
npm install
npm run build        # o `npm run dev` para desarrollo con recarga en caliente (junto a php artisan serve)
npm test             # Vitest: cliente API, dominio, video y rutas
npm run typecheck    # tsc --noEmit
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
