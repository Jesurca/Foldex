# Plan de trabajo – Repositorio de Proyectos UNAB (Laravel + PostgreSQL + API REST + MCP + Azure)

> Versión 5. Incluye roles (estudiante / admin), comentarios, likes, servidor MCP para conectar IAs (Claude, etc.) y **acceso exclusivo para correos @unab.edu.co**. Interfaces a cargo del equipo. Despliegue en Azure.
> Documentación completa para el evaluador (con guías paso a paso de las Fases 1 y 2, arquitectura y preguntas probables): https://claude.ai/artifact/QrHGyJwgfzd5P6QmmmKXwr
> Los tres integrantes usan este mismo proyecto de Claude: si no queda claro quién escribe, preguntar antes de asumir.

## Equipo y reparto (decidido el 2026-11-09, corregido el mismo día)
| Integrante | Módulo | Tareas por etapa |
|---|---|---|
| Daniel | Base del proyecto | E1: Fase 1 (entorno, repo, `install:api`) y Fase 2 (ER, migraciones, modelos) · E2: seeders + primer despliegue Azure + CI/CD · E3: servidor MCP + tokens personales · E4: pantalla "Conectar con IA", despliegue final, seguridad |
| Jesús | Autenticación y admin | E1: estudiar Sanctum/Policies, workspace Postman, revisar ER · E2: register/login/logout/me + regla @unab.edu.co + roles + Policies · E3: rutas /admin, pruebas Pest, Postman Auth/Admin · E4: pantallas login, registro, perfil, panel admin |
| Edwin | API de proyectos | E1: datos reales para seeders, bocetos de pantallas, revisar ER · E2: API de proyectos con filtros y catálogos (clases Action) · E3: comentarios, likes, /me, colección Postman · E4: pantallas inicio/listado, detalle, publicar/editar |

Puntos de sincronización: nadie programa módulos hasta que las migraciones estén en `develop`; "Blade o frontend separado" se decide antes de terminar la Etapa 2. Cada PR lo revisa otro integrante (Jesús revisa a Daniel, Edwin a Jesús, Daniel a Edwin) y actualiza la documentación.

## Idea del orden
**Datos → autenticación y roles → API REST → Postman → MCP → interfaces → despliegue**, con un **despliegue mínimo temprano** (Fase 3) para no descubrir problemas de Azure al final. El MCP va después de la API porque reutiliza la misma lógica (modelos, validaciones, permisos).

---

## Fase 0 – Alcance (MVP)
**Entra:**
- Login / register / logout para estudiantes y admins, **solo con correo @unab.edu.co**.
- Publicar proyecto: título, contexto/descripción, link(s) de repositorio, video de la app funcionando, materia, docente que dirigió, tecnologías usadas. Nombre y correo del publicante salen de su cuenta; la **fecha de publicación la pone el sistema** (`created_at`).
- Listar, buscar y filtrar proyectos (materia, semestre, tecnología, docente, texto).
- Comentarios y likes.
- Perfil del estudiante: "mis publicaciones" y "proyectos que me gustaron".
- Panel de admin: borrar publicaciones, comentarios y cuentas.
- Servidor MCP para que una IA consulte proyectos y ayude a publicar.

**No entra (por ahora):** notificaciones por correo (salvo la verificación de cuenta, si se decide), respuestas anidadas a comentarios, subir videos al servidor (se usan links de YouTube/Drive), estadísticas, login con Microsoft.

## Fase 1 – Entorno
1. Repositorio en GitHub: `main` (se despliega), `develop`, ramas `feature/...`.
2. Proyecto **Laravel 13** (estable desde el 17-03-2026) con **PHP 8.3+** (Laravel 13 ya no acepta 8.2) y PostgreSQL local (`DB_CONNECTION=pgsql`).
3. `php artisan install:api` (Sanctum + `routes/api.php`) y ruta de prueba `GET /api/v1/ping`.
4. `.env.example` actualizado; nunca subir `.env`.
5. Convenciones: tablas en español plural snake_case, modelos singular PascalCase, Conventional Commits en español, PR con 1 aprobación.

## Fase 2 – Modelo de datos
Diagrama ER (dbdiagram.io; el código DBML está en la sección 5 de la documentación) y luego migraciones + modelos con relaciones. Orden de creación: materias, docentes, tecnologias, proyectos, proyecto_tecnologia, comentarios, likes (las tablas con FK después de las tablas a las que apuntan).

| Tabla | Campos principales |
|---|---|
| `users` | id, name, email (único, @unab.edu.co), password, role (`estudiante` / `admin`), programa, semestre, email_verified_at, timestamps |
| `materias` | id, codigo, nombre, semestre |
| `docentes` | id, nombre, email (catálogo; el estudiante lo elige) |
| `tecnologias` | id, nombre (Laravel, React, PostgreSQL…) |
| `proyectos` | id, user_id (publicante), materia_id, docente_id, titulo, slug, contexto, repositorio_url, video_url, periodo, timestamps (`created_at` = fecha de publicación), softDeletes |
| `proyecto_tecnologia` | proyecto_id, tecnologia_id (PK compuesta) |
| `comentarios` | id, proyecto_id, user_id, contenido, timestamps, softDeletes |
| `likes` | user_id, proyecto_id (PK compuesta: un like por usuario), created_at |

Notas:
- Nombre y correo del publicante **no se guardan en `proyectos`**: se obtienen de `users` por la relación (evita datos duplicados).
- `softDeletes` permite que el admin "borre" sin perder la información.
- FK: `restrictOnDelete` hacia materias y docentes (no se borra un catálogo en uso); `cascadeOnDelete` desde users y proyectos (pendiente confirmar).
- `user_id` y `role` no son asignables en masa (`$fillable`): el autor sale del usuario autenticado y nadie se vuelve admin desde el registro.
- Factories y seeders para catálogos (materias reales de Sistemas UNAB, docentes, tecnologías) y un admin inicial (con correo @unab.edu.co). Las factories de usuarios también deben generar correos `@unab.edu.co`. Postman se usa para crear proyectos, comentarios y likes de prueba. Reiniciar todo: `php artisan migrate:fresh --seed`.

## Fase 3 – Primer despliegue en Azure (app casi vacía)
1. Azure App Service (Linux, PHP 8.3+) + Azure Database for PostgreSQL Flexible Server.
2. Variables de entorno en App Service (APP_KEY, DB_*, APP_ENV=production, APP_DEBUG=false, ALLOWED_EMAIL_DOMAIN).
3. Raíz del sitio apuntando a `/public` (script de inicio / config de nginx en App Service Linux).
4. Migraciones y seeders en producción.
5. CI/CD con GitHub Actions: push a `main` → despliegue.

## Fase 4 – Autenticación y roles
**Herramienta:** Laravel Sanctum (tokens de API).

**Endpoints:** `POST /register`, `POST /login`, `POST /logout`, `GET /me`.

### Restricción de dominio @unab.edu.co (obligatoria)
Se valida en **dos lugares**, para que no haya forma de saltarla:
1. **Registro** (`RegisterRequest`):
   ```php
   'email' => ['required', 'email', 'max:255', 'unique:users,email', 'ends_with:@unab.edu.co'],
   ```
   Mensaje: "Solo se permiten correos institucionales @unab.edu.co".
2. **Login** (`LoginRequest`): la misma regla `ends_with:@unab.edu.co`, para rechazar de una vez cualquier otro correo (por ejemplo, cuentas creadas antes por error o directo en la BD).
3. Normalizar el correo antes de validar (`strtolower(trim($email))`), para que `Juan@UNAB.edu.co` funcione igual y no se cuele algo como `x@unab.edu.co.fake.com` (por eso `ends_with` con la `@` incluida).
4. Guardar el dominio en config (`config/auth.php` → `'allowed_domain' => env('ALLOWED_EMAIL_DOMAIN', 'unab.edu.co')`) para no repetirlo en el código.
5. **Recomendado:** verificación de correo (`MustVerifyEmail`). Sin ella, alguien podría registrarse con un correo @unab.edu.co que no es suyo. Requiere configurar el envío de correos (SMTP o un servicio de correo).

**Roles y permisos:**
| Acción | Visitante | Estudiante | Admin |
|---|---|---|---|
| Ver / buscar proyectos | ✅ | ✅ | ✅ |
| Registrarse / login / logout (solo @unab.edu.co) | ✅ (registro) | ✅ | ✅ |
| Publicar proyecto | ❌ | ✅ | ✅ |
| Editar / borrar **su** proyecto | ❌ | ✅ | ✅ |
| Borrar **cualquier** proyecto | ❌ | ❌ | ✅ |
| Comentar | ❌ | ✅ | ✅ |
| Borrar **su** comentario | ❌ | ✅ | ✅ |
| Borrar cualquier comentario | ❌ | ❌ | ✅ |
| Dar / quitar like | ❌ | ✅ | ✅ |
| Ver mis publicaciones y mis likes | ❌ | ✅ | ✅ |
| Listar / borrar cuentas | ❌ | ❌ | ✅ |
| Gestionar catálogos (materias, docentes, tecnologías) | ❌ | ❌ | ✅ |
| Crear otros admins | ❌ | ❌ | ✅ |

**Implementación:**
1. Campo `role` en `users` (o un enum PHP `Role`).
2. Registro público **siempre crea estudiantes**. Los admins se crean con seeder o por otro admin (`POST /admin/users`, también con validación @unab.edu.co), nunca desde el registro abierto.
3. Middleware `role:admin` para el grupo de rutas `/admin`.
4. **Policies** (`ProyectoPolicy`, `ComentarioPolicy`): "el dueño o un admin puede borrar".

## Fase 5 – API REST (núcleo)
Rutas en `routes/api.php` con prefijo `/api/v1`, Form Requests para validar, API Resources para el JSON, paginación y errores uniformes (401, 403, 404, 422).

| Método | Ruta | Quién |
|---|---|---|
| GET | /proyectos?materia=&tecnologia=&docente=&q= | público |
| GET | /proyectos/{id} (incluye autor, tecnologías, n.º de likes, comentarios) | público |
| POST | /proyectos | estudiante |
| PUT/PATCH | /proyectos/{id} | dueño / admin |
| DELETE | /proyectos/{id} | dueño / admin |
| GET | /proyectos/{id}/comentarios | público |
| POST | /proyectos/{id}/comentarios | estudiante |
| DELETE | /comentarios/{id} | dueño / admin |
| POST | /proyectos/{id}/like | estudiante |
| DELETE | /proyectos/{id}/like | estudiante |
| GET | /me/proyectos | estudiante |
| GET | /me/likes | estudiante |
| GET | /materias, /docentes, /tecnologias | público |
| POST/PUT/DELETE | /admin/materias, /admin/docentes, /admin/tecnologias | admin |
| GET / DELETE | /admin/users, /admin/users/{id} | admin |
| POST | /admin/users (crear admin) | admin |

Recomendación: poner la lógica (crear proyecto, dar like, etc.) en clases **Action/Service** (`app/Actions/PublicarProyecto.php`). Así el controlador de la API y las herramientas del MCP llaman al mismo código.

## Fase 6 – Postman (en paralelo a la Fase 5)
1. Colección por carpetas: Auth, Proyectos, Comentarios, Likes, Perfil, Admin.
2. Entornos `local` y `azure` con `{{base_url}}`, `{{token_estudiante}}`, `{{token_admin}}` (guardados automáticamente tras login).
3. Probar permisos: un estudiante intentando borrar el proyecto de otro debe recibir 403.
4. Probar dominio: registro y login con `@gmail.com` → 422; con `@unab.edu.co` → OK; con mayúsculas (`@UNAB.EDU.CO`) → OK.
5. Exportar la colección a `/docs/postman` en el repo.
6. (Recomendado) Pruebas automáticas con Pest/PHPUnit para auth (incluido el dominio), permisos y likes.

## Fase 7 – Servidor MCP (conexión con IA)
**Qué es:** un servidor MCP (Model Context Protocol) expuesto por la misma app Laravel, al que un estudiante conecta su cliente de IA (Claude Desktop, Claude Code, etc.). La IA podrá buscar proyectos, explicar uno y ayudar a publicar.

**Herramienta:** paquete oficial `laravel/mcp`.
1. `composer require laravel/mcp`
2. `php artisan vendor:publish --tag=ai-routes` → crea `routes/ai.php`.
3. `php artisan make:mcp-server ProyectosServer`
4. Registrar como servidor web: `Mcp::web('/mcp/proyectos', ProyectosServer::class)->middleware(['auth:sanctum', 'throttle:mcp']);`
5. Crear herramientas con `php artisan make:mcp-tool ...`

**Herramientas (tools) propuestas:**
| Tool | Hace | Permiso |
|---|---|---|
| `buscar-proyectos` | Busca por texto, materia, tecnología | lectura |
| `ver-proyecto` | Devuelve detalle, links, video, comentarios | lectura |
| `listar-catalogos` | Materias, docentes, tecnologías válidas | lectura |
| `publicar-proyecto` | Crea un proyecto con los datos que el estudiante da a la IA | estudiante autenticado |
| `comentar-proyecto` | Agrega un comentario | estudiante autenticado |
| `mis-proyectos` | Lista lo publicado por el usuario | estudiante autenticado |

**Recursos / prompts (opcional):** un *resource* con las reglas de publicación (qué campos son obligatorios, formato de links) y un *prompt* "ayúdame a publicar mi proyecto" que guía a la IA paso a paso.

**Seguridad:**
- Cada tool reutiliza las Actions y Policies de la API (un estudiante no puede borrar lo de otro ni por MCP).
- Herramientas de borrado/admin **no** se exponen por MCP en el MVP.
- Validar argumentos en cada tool con mensajes claros (la IA corrige según el error).
- Autenticación: token personal de Sanctum que el estudiante genera desde su perfil (`POST /me/tokens`) y pega en la configuración de su cliente de IA. Como el token solo se obtiene tras login, el MCP también queda limitado a cuentas @unab.edu.co. Si luego quieren conectar como conector desde claude.ai, se necesita OAuth (Laravel Passport) – dejarlo como mejora.

**En la interfaz:** en el detalle de cada proyecto y en el perfil, una sección "Conectar con IA" con la URL del MCP, botón para generar token y las instrucciones de configuración.

**Pruebas:** MCP Inspector (`php artisan mcp:inspector ...`) y luego un cliente real (Claude Desktop/Code).

## Fase 8 – Interfaces (equipo)
Consumen la API ya probada. Pantallas mínimas: inicio/listado con filtros · detalle (comentarios, likes, "Conectar con IA") · login/register (con aviso "usa tu correo @unab.edu.co") · publicar/editar proyecto · perfil (mis proyectos, mis likes, tokens) · panel admin (proyectos, comentarios, cuentas, catálogos). Si el frontend es separado: configurar CORS.

## Fase 9 – Despliegue final y cierre
1. Desplegar todo, migraciones y seeders en Azure; crear admin inicial.
2. Probar la colección Postman contra `azure` y el MCP con un cliente real apuntando a la URL de Azure (HTTPS).
3. Seguridad: APP_DEBUG=false, HTTPS, CORS restringido, rate limiting en login, comentarios y MCP.
4. Documentación: README, diagrama ER, documentación de la API (Postman o Scribe), guía "cómo conectar tu IA al MCP", manual corto de usuario. Todo se consolida en el documento de documentación enlazado arriba.

---

## Decisiones tomadas
- Solo se aceptan correos **@unab.edu.co** en registro y login (validado en ambos).
- Laravel 13 con PHP 8.3+ (2026-11-09).
- Equipo de 3: Daniel (base, Azure, MCP), Jesús (autenticación, admin), Edwin (API de proyectos, comentarios, likes, Postman) (2026-11-09).

## Preguntas abiertas
1. ¿Interfaces en Blade (dentro de Laravel) o frontend separado (React/Vue/Angular)? Decidir antes de terminar la Etapa 2.
2. ¿Los profesores tienen cuenta propia para comentar (rol `docente`) o comentan como estudiantes? El "docente que dirigió", ¿se elige de una lista o se escribe a mano?
3. ¿Un proyecto puede tener varios autores?
4. ¿Se activa la verificación de correo (envío de enlace al @unab.edu.co)? Recomendado.
5. ¿El comité debe aprobar proyectos antes de que sean públicos, o se publican directo y el admin modera?
6. Fecha de entrega (para poner fechas a las etapas).
7. ¿Tienen cuenta Azure for Students?
8. Si se elimina una cuenta, ¿se borran sus proyectos (cascade) o se usa softDeletes también en `users`?
