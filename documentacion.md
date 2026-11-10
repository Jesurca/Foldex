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
routes/api.php             ← /api/v1/...
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

### Avanzadas
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
