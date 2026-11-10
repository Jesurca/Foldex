# Repositorio de Proyectos UNAB — API

API REST (Laravel 13 + PostgreSQL + Sanctum) donde los estudiantes de Ingeniería de Sistemas de la UNAB publican los proyectos de programación de sus materias. Solo se aceptan correos `@unab.edu.co`.

## Requisitos
- PHP 8.3+ con extensiones `pdo_pgsql`, `pgsql`, `mbstring`, `openssl`, `intl`, `zip`
- Composer 2
- Docker (para PostgreSQL local) o PostgreSQL 15+

## Instalación
```bash
composer install
cp .env.example .env
php artisan key:generate
docker compose up -d                 # PostgreSQL en localhost:5432
php artisan migrate --seed
```
Para las pruebas, crear una vez la BD de testing:
```bash
docker exec proyectos_unab_db psql -U proyectos -d proyectos_unab -c "CREATE DATABASE proyectos_unab_test"
```

## Ejecución
```bash
php artisan serve                    # http://localhost:8000/api/v1/ping
./vendor/bin/pest                    # pruebas
php artisan route:list --path=api/v1
```
Admin inicial (local): `admin@unab.edu.co` / `Admin12345*` (cambiar con `ADMIN_EMAIL` / `ADMIN_PASSWORD`).

La bitácora técnica y las preguntas de sustentación están en [`documentacion.md`](documentacion.md).

## Autores
Daniel · Jesús · Edwin — Desarrollo Backend, Ingeniería de Sistemas UNAB (docente Fabián Enrique Suárez Carvajal).

Fecha: noviembre de 2026.
