# Foldex — Repositorio de Proyectos UNAB

Aplicación donde los estudiantes de Ingeniería de Sistemas de la UNAB publican los proyectos de programación de sus materias. Solo se aceptan correos `@unab.edu.co`.

- **API REST**: Laravel 13 + PostgreSQL + Sanctum (token Bearer), en `/api/v1`.
- **Servidor MCP**: `/mcp/proyectos`, para publicar desde Claude con el botón "Conéctate por MCP".
- **Interfaz web**: React 19 + TanStack Router/Query + Tailwind v4, servida por Laravel como SPA (mismo origen que la API).

## Flujo de la página
1. **Inicio (`/`)**: carrusel, ranking de los proyectos con más "me gusta" (diario, semanal, mensual, anual o de siempre), búsqueda y filtros por semestre, materia, docente y tecnología. Se ve sin cuenta.
2. **Ingresar / registrarse**: solo correos `@unab.edu.co`. El servidor entrega un token que la página guarda y envía en cada petición.
3. **Detalle (`/proyectos/{slug}`)**: portada o video, contexto, tecnologías, enlace al repositorio, "me gusta" y comentarios.
4. **Publicar (`/publicar`)**: formulario con foto de portada opcional, o desde Claude con el botón "Conéctate por MCP" (solo se da el repositorio y el docente).
5. **Mi espacio (`/mi-espacio`)**: mis publicaciones y los proyectos que me gustan.
6. **Administración (`/administracion`, solo admin)**: editar o eliminar cualquier proyecto y gestionar cuentas.

Por dentro: React llama a `/api/v1` → Laravel valida (FormRequest), revisa permisos (Policies y `role:admin`) → guarda en PostgreSQL → responde JSON.

## Estructura
Es un solo proyecto Laravel, separado en dos carpetas:
```
backend/    app, config, database, lang y routes (Laravel)
frontend/   js (React), css, views (HTML base) y contexto_img (fotos del carrusel)
tests/      pruebas del backend (Pest las busca en la raíz)
docs/postman/  colección y entorno de Postman
```
`bootstrap/app.php` y `composer.json` apuntan a esas carpetas, así que los comandos de siempre (`php artisan …`, `npm run …`) funcionan igual.

## Requisitos
- PHP 8.3+ con extensiones `pdo_pgsql`, `pgsql`, `mbstring`, `openssl`, `intl`, `zip`
- Composer 2
- Node.js 22.12+ (Vite 8)
- PostgreSQL 15+, instalado en el equipo o con Docker

## Instalación
```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed           # en local también carga datos de demostración
php artisan storage:link             # sin esto, las fotos de portada dan 404
npm install
npm run build
```

**Base de datos.** El `.env` espera la base `proyectos_unab` en `127.0.0.1:5432`, con usuario `proyectos` y contraseña `secret`. Las pruebas usan una segunda base, `proyectos_unab_test`, que se crea una sola vez:
- **PostgreSQL instalado en el equipo**: créalas con pgAdmin, o con `createdb -U proyectos proyectos_unab` y `createdb -U proyectos proyectos_unab_test`.
- **Con Docker**: `docker compose up -d` levanta PostgreSQL con la base principal. La de pruebas se crea con `docker exec proyectos_unab_db psql -U proyectos -d proyectos_unab -c "CREATE DATABASE proyectos_unab_test"`.

Para ver las tablas en forma gráfica, conecta la extensión de PostgreSQL de VS Code (o Database Client, o pgAdmin) con esos mismos datos.

**Después de un `git pull`**, corre `composer install`, `npm install`, `php artisan migrate` y `npm run build`. Si tu `.env` es anterior al cambio de nombre, pon `APP_NAME=Foldex`.

## Ejecución
```bash
php artisan serve                    # http://localhost:8000 (interfaz) · http://localhost:8000/api/v1/ping (API)
npm run dev                          # opcional, en otra terminal: recarga en caliente del frontend
./vendor/bin/pest --testsuite=Feature   # pruebas del backend
npm test                             # pruebas del frontend (Vitest)
npm run typecheck                    # tsc --noEmit
php artisan route:list --path=api/v1
```

## Cuentas y permisos
- Admin: `fabian@unab.edu.co` / `Admin12345*`. Se cambia con `ADMIN_EMAIL` / `ADMIN_PASSWORD` y se aplica con `php artisan db:seed --class=AdminSeeder`.
- Estudiantes de demostración: `sgomez@unab.edu.co`, `vtorres@unab.edu.co`, `dredondo@unab.edu.co` / `Estudiante123`.

| | Estudiante | Admin |
|---|---|---|
| Publicar proyectos (web o Claude) | ✔ | ✔ |
| Borrar sus propios proyectos | ✔ | ✔ |
| Editar o borrar cualquier proyecto | ✘ | ✔ |
| Panel `/administracion` | ✘ | ✔ |

## Publicar desde Claude (MCP)
En la web, el botón **Conéctate por MCP** (en "Publicar" o en el detalle de un proyecto) genera una llave y muestra cómo agregar el servidor a Claude Code o Claude Desktop. Después basta con un mensaje como:
```
Publica mi proyecto en Foldex.
Repositorio: https://github.com/usuario/mi-proyecto
Docente: Fabián Suárez
```
Claude lee el repositorio y completa la descripción, la materia, las tecnologías y la foto. El proyecto se publica a nombre de la cuenta dueña de la llave. En local funciona con Claude Code y Claude Desktop; claude.ai (web) necesita el sitio publicado con HTTPS.

## Carrusel de la portada
Las fotos salen de `frontend/contexto_img/` (`.jpg`, `.png`, `.webp`, `.avif`) y se leen en vivo, así que no hay que recompilar. Se muestran en orden alfabético por nombre de archivo. Funcionan mejor las fotos horizontales.

## Probar la API con Postman
En Postman, pulsa **Import** y arrastra los dos archivos de `docs/postman/`. Elige el entorno **Foldex local**, enciende el servidor con `php artisan serve` y ejecuta la colección con **Run**. Son 51 peticiones con pruebas que recorren toda la API y, al final, borran lo que crearon.

La bitácora técnica y las preguntas de sustentación están en [`documentacion.md`](documentacion.md).

## Autores
Daniel · Jesús · Edwin — Desarrollo Backend, Ingeniería de Sistemas UNAB (docente Fabián Enrique Suárez Carvajal).

Fecha: noviembre de 2026.
