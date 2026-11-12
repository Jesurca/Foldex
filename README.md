# Repositorio de Proyectos UNAB

Aplicación donde los estudiantes de Ingeniería de Sistemas de la UNAB publican los proyectos de programación de sus materias. Solo se aceptan correos `@unab.edu.co`.

- **API REST**: Laravel 13 + PostgreSQL + Sanctum (token Bearer), en `/api/v1` (código en `backend/`).
- **Servidor MCP**: `/mcp/proyectos`, para publicar desde Claude con el botón "Conéctate por MCP".
- **Interfaz web**: React 19 + TanStack Router/Query + Tailwind v4, en `frontend/js`, servida por Laravel como SPA (mismo origen que la API).

## Requisitos
- PHP 8.3+ con extensiones `pdo_pgsql`, `pgsql`, `mbstring`, `openssl`, `intl`, `zip`
- Composer 2
- Node.js 22.12+ (Vite 8)
- Docker (para PostgreSQL local) o PostgreSQL 15+

## Instalación
```bash
composer install
cp .env.example .env
php artisan key:generate
docker compose up -d                 # PostgreSQL en localhost:5432 (o usa un PostgreSQL local con los datos del .env)
php artisan migrate --seed           # en local también carga datos de demostración
npm install
npm run build
```
Para las pruebas, crear una vez la BD de testing:
```bash
docker exec proyectos_unab_db psql -U proyectos -d proyectos_unab -c "CREATE DATABASE proyectos_unab_test"
```

## Ejecución
```bash
php artisan serve                    # http://localhost:8000 (interfaz) · http://localhost:8000/api/v1/ping (API)
npm run dev                          # opcional, en otra terminal: recarga en caliente del frontend
./vendor/bin/pest --testsuite=Feature   # pruebas del backend
npm test                             # pruebas del frontend (Vitest)
npm run typecheck                    # tsc --noEmit
php artisan route:list --path=api/v1
```
Cuentas locales:
- Admin: `fabian@unab.edu.co` / `Admin12345*` (cambiar con `ADMIN_EMAIL` / `ADMIN_PASSWORD`).
- Estudiantes de demostración: `sgomez@unab.edu.co`, `vtorres@unab.edu.co`, `dredondo@unab.edu.co` / `Estudiante123`.

La bitácora técnica y las preguntas de sustentación están en [`documentacion.md`](documentacion.md).

## Autores
Daniel · Jesús · Edwin — Desarrollo Backend, Ingeniería de Sistemas UNAB (docente Fabián Enrique Suárez Carvajal).

Fecha: noviembre de 2026.
