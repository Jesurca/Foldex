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

## Preguntas de sustentación

### Básicas
**¿Qué es una API REST y un endpoint?**
Una API REST expone recursos (proyectos, comentarios) por HTTP usando URLs y métodos con significado; responde JSON. Un endpoint es la combinación método + URL, p. ej. `GET /api/v1/proyectos`.

**¿Para qué sirve `.env`?**
Guarda la configuración que cambia por entorno (credenciales de BD, APP_KEY, APP_DEBUG). No se sube a Git porque contiene secretos; se versiona `.env.example` como plantilla.

**¿Qué es Docker: imagen vs contenedor?**
Una imagen es una plantilla inmutable (SO + dependencias, p. ej. `postgres:17-alpine`); un contenedor es una instancia en ejecución de esa imagen. Aquí PostgreSQL corre en un contenedor con un volumen (`pgdata`) para persistir datos.

### Avanzadas
**¿Por qué versionar la API (`/api/v1`)?**
Permite cambiar el contrato en una `v2` sin romper clientes existentes (frontend Lovable, servidor MCP). Los clientes migran a su ritmo.

**¿Por qué CORS con frontend en otro dominio?**
El navegador bloquea peticiones `fetch` a un origen distinto salvo que el servidor lo autorice con cabeceras CORS. Se autoriza solo `FRONTEND_URL` (no `*`) para que otros sitios no puedan usar la API desde el navegador de un usuario.

**¿Por qué las pruebas usan PostgreSQL y no SQLite en memoria?**
SQLite es más rápido pero no soporta igual `ilike`, tipos ni restricciones; probar con el mismo motor de producción evita falsos positivos.
