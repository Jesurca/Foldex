# Frontend – Repositorio de Proyectos UNAB

> Especificación y prompt para construir las interfaces del sistema (Fase 8 del plan de trabajo).
> Sirve para dos cosas: (1) que el equipo sepa exactamente qué construir y (2) pegarlo completo como contexto a una IA (Claude, Claude Code, etc.) para que genere el código siguiendo las mismas reglas.
> Todo lo marcado con **⚠ Backend** es algo que la API todavía no tiene en el plan y hay que acordar con quien la programa.

---

## 1. Contexto del producto

Plataforma web de la **Universidad Autónoma de Bucaramanga (UNAB)**, dirigida al **comité institucional del área de Sistemas**, donde los estudiantes publican los proyectos de programación que hacen en sus materias para que queden como biblioteca consultable.

Cada proyecto tiene: título, contexto/descripción, link del repositorio, video de la app funcionando (link de YouTube o Drive), materia, semestre, periodo académico, docente que lo dirigió y tecnologías usadas. **El nombre y el correo del publicante salen de su cuenta** y **la fecha de publicación la pone el sistema** (`created_at`): el formulario nunca los pide.

Los proyectos se pueden comentar y recibir "me gusta". Cada estudiante puede conectar su IA (Claude u otra) al **servidor MCP** del sistema para buscar proyectos y publicar con ayuda de la IA.

Solo se aceptan cuentas con correo **@unab.edu.co**.

### Roles

| Acción | Visitante | Estudiante | Admin |
|---|---|---|---|
| Ver, buscar y filtrar proyectos | ✅ | ✅ | ✅ |
| Registrarse / iniciar sesión (solo @unab.edu.co) | ✅ | – | – |
| Publicar proyecto | ❌ | ✅ | ✅ |
| Editar / borrar **su** proyecto | ❌ | ✅ | ✅ |
| Borrar **cualquier** proyecto o comentario | ❌ | ❌ | ✅ |
| Comentar, borrar su comentario | ❌ | ✅ | ✅ |
| Dar / quitar me gusta | ❌ | ✅ | ✅ |
| Perfil: mis publicaciones, mis me gusta, tokens de IA | ❌ | ✅ | ✅ |
| Panel admin: cuentas, catálogos, crear admins | ❌ | ❌ | ✅ |

**Regla de oro:** ocultar un botón en la interfaz es solo comodidad (UX). La seguridad real la ponen el backend (middleware `auth:sanctum`, `role:admin` y las Policies). El frontend nunca decide permisos por su cuenta; si la API responde 403, se muestra el error.

---

## 2. Stack recomendado

> La decisión "Blade o frontend separado" sigue abierta en el plan. Esta es la recomendación; si eligen Blade, ver el Anexo B.

**SPA en React + TypeScript dentro del mismo repositorio Laravel**, compilada con Vite (que Laravel ya trae) y servida por Laravel en el mismo dominio:

- Una sola app para desplegar en Azure App Service (el build de Vite corre en GitHub Actions).
- Mismo origen que la API → **no hace falta CORS**.
- El frontend consume la misma API REST `/api/v1` que se prueba en Postman. Eso demuestra ante el evaluador que la API funciona de verdad.

| Necesidad | Librería |
|---|---|
| UI | React 19 + TypeScript |
| Build | Vite + `laravel-vite-plugin` + `@vitejs/plugin-react` |
| Rutas | React Router |
| Datos del servidor (caché, recarga, paginación) | TanStack Query |
| Formularios y validación | React Hook Form + Zod |
| HTTP | `fetch` con un cliente propio (o axios) |
| Estilos | **CSS propio** con variables (design tokens) + CSS Modules. Sin Bootstrap (el curso lo pide así en los talleres). |
| Fuentes | Autoalojadas con `@fontsource` |
| Pruebas | Vitest + Testing Library (mínimo: login, publicar, like) |
| Calidad | ESLint + Prettier |

### Integración con Laravel

```php
// routes/web.php — todas las rutas que no sean API ni MCP devuelven la SPA
Route::view('/{any?}', 'app')->where('any', '^(?!api|mcp|up).*$');
```

```blade
{{-- resources/views/app.blade.php --}}
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Repositorio de Proyectos · UNAB</title>
  @viteReactRefresh
  @vite('resources/js/main.tsx')
</head>
<body><div id="app"></div></body>
</html>
```

```js
// vite.config.js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig({
  plugins: [laravel({ input: ['resources/js/main.tsx'], refresh: true }), react()],
});
```

Desarrollo local: `php artisan serve` + `npm run dev` en otra terminal.
Producción: `npm ci && npm run build` en el pipeline antes de desplegar.

### Estructura de carpetas

```
resources/js/
├── main.tsx                 # monta <App/>, QueryClientProvider, Router
├── router.tsx               # definición de rutas y guards
├── api/
│   ├── client.ts            # fetch base: URL, headers, token, manejo de errores
│   ├── auth.ts              # register, login, logout, me
│   ├── proyectos.ts         # listar, ver, crear, editar, borrar, like
│   ├── comentarios.ts
│   ├── catalogos.ts         # materias, docentes, tecnologias
│   ├── perfil.ts            # me/proyectos, me/likes, me/tokens
│   └── admin.ts
├── types/api.ts             # tipos TypeScript del contrato (sección 3)
├── auth/
│   ├── AuthContext.tsx      # usuario actual, token, login(), logout()
│   ├── RequireAuth.tsx      # redirige a /login?redirect=...
│   └── RequireAdmin.tsx     # muestra 403 si no es admin
├── components/
│   ├── layout/              # Header, Footer, Layout
│   ├── ui/                  # Button, Input, Select, MultiSelect, Chip, Card, Modal, Toast, Tabs, Spinner, Pagination
│   ├── ProyectoCard.tsx
│   ├── FiltrosProyectos.tsx
│   ├── LikeButton.tsx
│   ├── ComentariosSection.tsx
│   ├── VideoEmbed.tsx
│   ├── ConectarIA.tsx
│   ├── EmptyState.tsx
│   └── ErrorState.tsx
├── pages/
│   ├── Inicio.tsx
│   ├── ProyectoDetalle.tsx
│   ├── Login.tsx
│   ├── Registro.tsx
│   ├── ProyectoForm.tsx     # publicar y editar (misma pantalla)
│   ├── Perfil.tsx
│   ├── NoEncontrado.tsx
│   └── admin/
│       ├── AdminLayout.tsx
│       ├── AdminProyectos.tsx
│       ├── AdminComentarios.tsx
│       ├── AdminUsuarios.tsx
│       └── AdminCatalogos.tsx
├── lib/
│   ├── fechas.ts            # formato es-CO
│   ├── video.ts             # convierte links de YouTube/Drive a embed
│   └── validaciones.ts      # esquemas Zod compartidos
└── styles/
    ├── tokens.css           # colores, tipografía, espacios, radios, sombras
    └── global.css
```

---

## 3. Contrato con la API

- **Base:** `/api/v1` (mismo dominio).
- **Headers en todas las peticiones:** `Accept: application/json` y, si hay sesión, `Authorization: Bearer <token>`. Con cuerpo: `Content-Type: application/json`.
- **Autenticación:** token de Laravel Sanctum devuelto por `POST /login`. Se guarda en `localStorage` (clave `unab_token`) y se carga al iniciar la app llamando a `GET /me`. Si cualquier petición responde **401**, se borra el token y se redirige a `/login?redirect=<ruta actual>`.
  - *Nota para el evaluador:* `localStorage` es vulnerable si hubiera XSS. React escapa todo el contenido por defecto y no se usa `dangerouslySetInnerHTML`. La mejora más segura es la autenticación de SPA de Sanctum por cookie (mismo dominio), que se deja como mejora futura.

### Formato de respuestas (el que genera Laravel)

```jsonc
// Lista paginada (API Resource + paginate)
{ "data": [ ... ], "links": { "first": "...", "last": "...", "prev": null, "next": "..." },
  "meta": { "current_page": 1, "last_page": 4, "per_page": 12, "total": 40 } }

// 422 – validación
{ "message": "Solo se permiten correos institucionales @unab.edu.co",
  "errors": { "email": ["Solo se permiten correos institucionales @unab.edu.co"] } }

// 401 – sin sesión            { "message": "Unauthenticated." }
// 403 – sin permiso           { "message": "This action is unauthorized." }
// 404 – no existe             { "message": "..." }
// 429 – demasiadas peticiones { "message": "Too Many Attempts." }
```

El cliente HTTP debe convertir esto en un error tipado `{ status, message, errors? }`. Los formularios ponen `errors[campo][0]` debajo de cada campo; los demás errores van en un toast o en `ErrorState`. **Traducir al español** los mensajes genéricos en inglés (401/403/404/429/500).

### Endpoints que usa el frontend

| Método | Ruta | Uso en pantalla | Auth |
|---|---|---|---|
| POST | /register | Registro | – |
| POST | /login | Login → `{ token, user }` | – |
| POST | /logout | Botón "Cerrar sesión" | ✅ |
| GET | /me | Cargar sesión al abrir la app | ✅ |
| GET | /proyectos?q=&materia=&tecnologia=&docente=&semestre=&page= | Inicio | – |
| GET | /proyectos/{id} | Detalle | – |
| POST | /proyectos | Publicar | ✅ |
| PUT | /proyectos/{id} | Editar | dueño/admin |
| DELETE | /proyectos/{id} | Borrar | dueño/admin |
| GET | /proyectos/{id}/comentarios?page= | Detalle | – |
| POST | /proyectos/{id}/comentarios | Comentar | ✅ |
| DELETE | /comentarios/{id} | Borrar comentario | dueño/admin |
| POST / DELETE | /proyectos/{id}/like | Botón me gusta | ✅ |
| GET | /me/proyectos | Perfil → Mis publicaciones | ✅ |
| GET | /me/likes | Perfil → Me gusta | ✅ |
| POST | /me/tokens | Conectar con IA → generar token | ✅ |
| GET | /materias, /docentes, /tecnologias | Filtros y formulario | – |
| GET / DELETE | /admin/users, /admin/users/{id} | Admin → Usuarios | admin |
| POST | /admin/users | Admin → Crear admin | admin |
| POST/PUT/DELETE | /admin/materias, /admin/docentes, /admin/tecnologias | Admin → Catálogos | admin |

### Tipos (contrato propuesto: confirmar con los API Resources del backend)

```ts
// resources/js/types/api.ts
export type Rol = 'estudiante' | 'admin';

export interface Usuario {
  id: number;
  name: string;
  email: string;           // siempre @unab.edu.co
  role: Rol;
  programa: string | null;
  semestre: number | null;
}

export interface Materia { id: number; codigo: string; nombre: string; semestre: number }
export interface Docente { id: number; nombre: string; email: string | null }
export interface Tecnologia { id: number; nombre: string }

export interface Proyecto {
  id: number;
  slug: string;
  titulo: string;
  contexto: string;
  repositorio_url: string;
  video_url: string | null;
  periodo: string;               // ej. "2026-2"
  publicado_en: string;          // ISO 8601, viene de created_at
  autor: Pick<Usuario, 'id' | 'name' | 'email' | 'programa' | 'semestre'>;
  materia: Materia;
  docente: Docente;
  tecnologias: Tecnologia[];
  likes_count: number;
  comentarios_count: number;
  liked_by_me?: boolean;         // solo si la petición lleva token
  can?: { update: boolean; delete: boolean }; // calculado con las Policies
}

export interface Comentario {
  id: number;
  contenido: string;
  creado_en: string;
  autor: Pick<Usuario, 'id' | 'name' | 'role'>;
  can?: { delete: boolean };
}

export interface Paginado<T> {
  data: T[];
  meta: { current_page: number; last_page: number; per_page: number; total: number };
}

export interface ProyectoInput {
  titulo: string;
  contexto: string;
  repositorio_url: string;
  video_url?: string;
  periodo: string;
  materia_id: number;
  docente_id: number;
  tecnologias: number[];         // ids
}
```

### ⚠ Backend: cosas que el frontend necesita y el plan aún no tiene

1. `liked_by_me` y `can` en `ProyectoResource`, y `can.delete` en `ComentarioResource`. En rutas públicas se puede leer el usuario opcional con `$request->user('sanctum')`.
2. `likes_count` y `comentarios_count` en el listado (`withCount`).
3. Filtro `semestre=` en `GET /proyectos` (filtra por `materias.semestre`) y orden `orden=recientes|populares`.
4. `POST /proyectos/{id}/like` y `DELETE` deben responder `{ "liked": true|false, "likes_count": n }`.
5. `GET /me/tokens` (lista: id, name, last_used_at, created_at) y `DELETE /me/tokens/{id}`. `POST /me/tokens` devuelve el token en texto plano **una sola vez**.
6. `GET /admin/comentarios?page=` para moderar comentarios desde el panel.
7. Respuesta de `POST /login`: `{ token, user }`; de `POST /register`: lo mismo (para entrar directo tras registrarse).
8. Que todos los mensajes de validación estén en español (`lang/es/validation.php` o mensajes en cada Form Request).

---

## 4. Rutas y pantallas

| Ruta del frontend | Pantalla | Acceso | Responsable (Etapa 4) |
|---|---|---|---|
| `/` | Inicio / listado con filtros | público | Edwin |
| `/proyectos/:id` | Detalle del proyecto | público | Edwin |
| `/publicar` | Publicar proyecto | estudiante | Edwin |
| `/proyectos/:id/editar` | Editar proyecto | dueño / admin | Edwin |
| `/login` | Iniciar sesión | visitante | Jesús |
| `/registro` | Crear cuenta | visitante | Jesús |
| `/perfil` | Perfil (pestañas) | estudiante | Jesús |
| `/perfil#ia` | Conectar con IA | estudiante | Daniel |
| `/admin/*` | Panel de administración | admin | Jesús |
| `*` | 404 | – | cualquiera |

**Toda pantalla que carga datos tiene cuatro estados:** cargando (skeleton, no spinner a pantalla completa), vacío (mensaje + acción, ej. "Aún no hay proyectos. ¡Publica el primero!"), error (mensaje en español + botón "Reintentar") y con datos.

### 4.1 Header (todas las páginas)
- Logo/nombre "Repositorio de Proyectos · Ingeniería de Sistemas UNAB" → `/`.
- Visitante: "Iniciar sesión", "Crear cuenta".
- Estudiante: botón principal "Publicar proyecto", menú con su nombre → Perfil, Cerrar sesión.
- Admin: lo mismo + enlace "Administración".
- En móvil: menú hamburguesa.

### 4.2 Inicio / listado (`/`)
- Encabezado corto que explica qué es el repositorio.
- **Buscador** de texto (`q`) con *debounce* de 400 ms.
- **Filtros:** materia, semestre (1–10), tecnología, docente (selects alimentados por los catálogos) y orden (recientes / más gustados). Botón "Limpiar filtros".
- **Los filtros viven en la URL** (`/?q=react&materia=3&page=2`): se puede compartir el enlace y el botón atrás funciona.
- Grilla de `ProyectoCard`: título, autor, materia y semestre, hasta 4 chips de tecnologías (+N), fecha relativa ("hace 3 días") con la fecha exacta en `title`, contadores de me gusta y comentarios.
- Paginación con `meta` (anterior / números / siguiente), 12 por página.

### 4.3 Detalle (`/proyectos/:id`)
- Título, autor (nombre + correo como enlace `mailto:`), programa y semestre del autor.
- Ficha: materia (código + nombre), semestre, periodo, docente que dirigió, **fecha de publicación** en formato largo es-CO ("9 de noviembre de 2026").
- Chips de tecnologías (al hacer clic → `/?tecnologia=<id>`).
- Botones: "Ver repositorio" (abre en pestaña nueva, `rel="noopener noreferrer"`) y video incrustado con `VideoEmbed`.
- Contexto/descripción en texto con saltos de línea respetados (`white-space: pre-line`). **No** renderizar HTML.
- `LikeButton`: actualización optimista (cambia al instante y revierte si la API falla). Sin sesión → lleva a `/login?redirect=...`.
- Si `can.update` / `can.delete`: botones "Editar" y "Eliminar" (con modal de confirmación).
- `ComentariosSection`: lista paginada (más recientes primero), insignia "Docente/Admin" según rol, formulario (1–1000 caracteres, contador), borrar si `can.delete`. Sin sesión: "Inicia sesión para comentar".
- Sección **"Conectar con IA"** (ver sección 5), colapsada por defecto.
- 404 si el proyecto no existe o fue borrado.

`VideoEmbed` (`lib/video.ts`) solo acepta estos formatos; cualquier otro se muestra como enlace normal:
- `youtube.com/watch?v=ID`, `youtu.be/ID`, `youtube.com/shorts/ID` → `https://www.youtube-nocookie.com/embed/ID`
- `drive.google.com/file/d/ID/view` → `https://drive.google.com/file/d/ID/preview`
- `iframe` con `loading="lazy"`, `allowfullscreen` y relación 16:9.

### 4.4 Login (`/login`) y Registro (`/registro`)
- Aviso visible: **"Usa tu correo institucional @unab.edu.co"**.
- El correo se normaliza (`trim` + minúsculas) antes de enviarlo.
- Validación en el cliente con Zod (misma regla que el backend, que es quien manda):
  ```ts
  const correoUnab = z.string().trim().toLowerCase().email('Correo no válido')
    .refine(v => v.endsWith('@unab.edu.co'), 'Solo se permiten correos institucionales @unab.edu.co');
  ```
- Registro: nombre, correo, programa (select; por defecto Ingeniería de Sistemas), semestre (1–10), contraseña (mín. 8) y confirmación. El registro **siempre crea estudiantes**: no hay campo de rol.
- Botón deshabilitado y con spinner mientras se envía. Errores 422 debajo de cada campo; 429 → "Demasiados intentos, espera un momento".
- Al entrar, volver a `redirect` si existe, si no a `/`.

### 4.5 Publicar / editar (`/publicar`, `/proyectos/:id/editar`)
Mismo componente `ProyectoForm`.

| Campo | Control | Regla |
|---|---|---|
| Título | input | requerido, 5–150 |
| Contexto / descripción | textarea con contador | requerido, 30–5000 |
| Link del repositorio | input url | requerido, `https://`, idealmente github.com o gitlab.com |
| Video de la app funcionando | input url | opcional, YouTube o Drive; muestra vista previa con `VideoEmbed` |
| Materia | select (catálogo) | requerido; al elegirla se muestra su semestre |
| Docente que dirigió | select con búsqueda (catálogo) | requerido |
| Periodo | select (`2026-2`, `2026-1`, …) | requerido |
| Tecnologías | multiselección con búsqueda (catálogo) | mínimo 1, máximo 10 |

- **No** hay campos de nombre, correo ni fecha. Se muestra un texto fijo: *"Se publicará como **{nombre}** ({correo}). La fecha de publicación se asigna automáticamente."*
- Al guardar → toast "Proyecto publicado" y redirección al detalle.
- Editar: precargar datos; si la API responde 403 → pantalla "No tienes permiso para editar este proyecto".
- Avisar antes de salir si hay cambios sin guardar.

### 4.6 Perfil (`/perfil`)
- Cabecera: nombre, correo, programa, semestre, rol.
- Pestañas:
  1. **Mis publicaciones** (`/me/proyectos`): tarjetas con acciones editar/eliminar.
  2. **Me gusta** (`/me/likes`): proyectos a los que dio me gusta.
  3. **Conectar con IA**: gestión de tokens (sección 5).
- Pestaña activa reflejada en la URL (`#publicaciones`, `#likes`, `#ia`).

### 4.7 Panel de administración (`/admin`)
Layout con menú lateral. Todo con confirmación antes de borrar.
- **Proyectos:** tabla (título, autor, materia, fecha, likes) con búsqueda y botón eliminar.
- **Comentarios:** tabla (contenido recortado, autor, proyecto, fecha) con eliminar. ⚠ Backend #6.
- **Usuarios:** tabla (nombre, correo, rol, fecha de registro), eliminar cuenta (no se permite eliminarse a sí mismo) y formulario "Crear administrador" (también solo @unab.edu.co).
- **Catálogos:** tres pestañas (materias, docentes, tecnologías) con crear, editar en línea y eliminar. Si la API rechaza borrar un catálogo en uso, mostrar "No se puede eliminar: hay proyectos que lo usan".

---

## 5. Conectar con IA (MCP)

Aparece en el detalle de cada proyecto (colapsado) y en la pestaña de perfil.

1. Explicación corta: *"Conecta tu asistente de IA (Claude Desktop, Claude Code u otro compatible con MCP) para buscar proyectos y publicar los tuyos conversando."*
2. URL del servidor (con botón copiar): `{APP_URL}/mcp/proyectos`
3. **Generar token** (requiere sesión): pide un nombre ("Mi Claude Desktop"), llama a `POST /me/tokens` y muestra el token **una sola vez** en un modal con botón copiar y aviso: *"Guárdalo ahora: no se volverá a mostrar. Trátalo como una contraseña."*
4. Lista de tokens (`GET /me/tokens`) con último uso y botón revocar. ⚠ Backend #5.
5. Instrucciones por cliente, con el token ya insertado si se acaba de generar:

**Claude Code**
```bash
claude mcp add --transport http unab-proyectos https://<dominio>/mcp/proyectos \
  --header "Authorization: Bearer <TOKEN>"
```

**Claude Desktop** (`claude_desktop_config.json`, usando el puente `mcp-remote`)
```json
{
  "mcpServers": {
    "unab-proyectos": {
      "command": "npx",
      "args": ["-y", "mcp-remote", "https://<dominio>/mcp/proyectos",
               "--header", "Authorization: Bearer <TOKEN>"]
    }
  }
}
```

6. Ejemplos de lo que se le puede pedir a la IA: "Busca proyectos de Laravel de quinto semestre", "Explícame el proyecto X", "Ayúdame a publicar mi proyecto: este es el repo…".
7. En el detalle de un proyecto, botón "Copiar prompt para la IA" con: *"Usa la herramienta ver-proyecto con id {id} y explícame cómo está construido."*

> Conectarlo como conector desde claude.ai necesita OAuth (Laravel Passport); queda como mejora, igual que en el plan.

---

## 6. Diseño visual (identidad UNAB)

El manual de marca de la UNAB define cuatro colores en CMYK y pide **variar los colores** en vez de usar solo uno. Los HEX de abajo son **conversiones aproximadas**: verifíquenlos con el manual oficial (https://unab.edu.co/wp-content/uploads/2022/01/Manual-de-marca-Unab.pdf) o con un cuentagotas sobre unab.edu.co antes de entregar.

```css
/* resources/js/styles/tokens.css */
:root {
  /* Marca (aprox. desde CMYK del manual) */
  --unab-naranja:  #F39200; /* C0 M45 Y100 K0 – color principal */
  --unab-verde:    #95C11F; /* C45 M0 Y100 K0 */
  --unab-cian:     #009FE3; /* C100 M0 Y0 K0 */
  --unab-morado:   #662483; /* C65 M100 Y0 K0 */

  /* Variantes accesibles para texto sobre blanco (contraste ≥ 4.5:1) */
  --naranja-texto: #A65F00;
  --cian-texto:    #006B99;

  /* Neutros */
  --fondo:       #FAFAF7;
  --superficie:  #FFFFFF;
  --borde:       #E4E2DC;
  --texto:       #1F1D1A;
  --texto-suave: #5E5A53;

  /* Estados */
  --exito: #2E7D32;  --error: #C62828;  --aviso: #A65F00;

  /* Tipografía */
  --fuente-titulos: 'Outfit Variable', system-ui, sans-serif;
  --fuente-texto:   'Source Sans 3 Variable', system-ui, sans-serif;
  --fuente-codigo:  'JetBrains Mono Variable', ui-monospace, monospace;

  /* Escala */
  --radio: 10px;  --radio-chip: 999px;
  --sombra: 0 1px 2px rgb(0 0 0 / .06), 0 4px 16px rgb(0 0 0 / .06);
  --espacio-1: 4px; --espacio-2: 8px; --espacio-3: 12px; --espacio-4: 16px;
  --espacio-6: 24px; --espacio-8: 32px; --espacio-12: 48px;
}
```

- La tipografía institucional (CastleT) es comercial: para web se usan Outfit (títulos) y Source Sans 3 (texto), libres y autoalojadas.
- **Naranja = acción principal** (botón "Publicar", like activo) con texto oscuro encima. El naranja puro sobre blanco **no** sirve para texto (contraste ≈ 2.3:1): para enlaces usar `--naranja-texto`.
- Verde, cian y morado: categorías visuales (chips de tecnologías por tipo: frontend, backend, base de datos, móvil), siguiendo la indicación de variedad del manual.
- Estética: catálogo académico limpio, mucho espacio en blanco, tarjetas con borde suave, las tecnologías y el código como protagonistas (fuente monoespaciada en nombres de repos y tecnologías).
- **Responsive** desde 360 px: 1 columna en móvil, 2 en tablet, 3 en escritorio.
- **Accesibilidad:** HTML semántico, `label` en todos los campos, foco visible, navegable con teclado, `alt` en imágenes, `aria-live` en toasts, contraste AA.
- **Idioma:** todo en español de Colombia, tono cercano y claro ("Publica tu proyecto", no "Submit"). Fechas con `Intl.DateTimeFormat('es-CO')`.
- Para el diseño puede usarse la skill **frontend-design** de Claude antes de escribir los estilos.

---

## 7. Seguridad en el frontend

- Nunca usar `dangerouslySetInnerHTML`; todo el contenido de usuarios se muestra como texto.
- Enlaces externos: `target="_blank" rel="noopener noreferrer"`; solo aceptar `https://`.
- `iframe` solo para dominios de YouTube y Drive (lista blanca en `lib/video.ts`).
- No guardar en el navegador nada más que el token. Borrarlo en logout y ante cualquier 401.
- Los tokens del MCP se muestran una sola vez y nunca se guardan en el frontend.
- No poner secretos en variables `VITE_*` (todo lo que empieza por `VITE_` queda público en el JavaScript).

---

## 8. Orden de construcción y commits

El profesor evalúa el historial de Git: **un commit por avance**, en español, con Conventional Commits, en ramas `feature/...` y PR revisado por otro integrante.

1. `feat(front): configurar React + Vite + TypeScript en Laravel` (ruta comodín, `app.blade.php`, tokens.css)
2. `feat(front): cliente API, tipos y manejo de errores`
3. `feat(front): contexto de autenticación y guards de rutas`
4. `feat(front): pantallas de login y registro con regla @unab.edu.co`
5. `feat(front): layout, header y componentes base`
6. `feat(front): inicio con búsqueda, filtros y paginación`
7. `feat(front): detalle de proyecto con video y like`
8. `feat(front): comentarios`
9. `feat(front): formulario publicar/editar proyecto`
10. `feat(front): perfil con mis publicaciones y me gusta`
11. `feat(front): conectar con IA y gestión de tokens`
12. `feat(front): panel de administración`
13. `test(front): pruebas de login, publicar y like`
14. `ci: compilar frontend en GitHub Actions antes de desplegar`

---

## 9. Checklist de aceptación

- [ ] Registro y login rechazan `@gmail.com` (mensaje en español) y aceptan `@UNAB.EDU.CO` normalizado.
- [ ] Sin sesión se puede ver, buscar y filtrar, pero no publicar, comentar ni dar like (redirige a login y luego vuelve).
- [ ] Los filtros quedan en la URL y sobreviven a recargar la página.
- [ ] El formulario de publicar no pide autor ni fecha; el detalle muestra la fecha que puso el sistema.
- [ ] Un estudiante no ve "Editar/Eliminar" en proyectos ajenos y, si fuerza la URL de edición, ve el 403.
- [ ] El like cambia al instante y el contador coincide tras recargar.
- [ ] Videos de YouTube y Drive se incrustan; otros links salen como enlace.
- [ ] Se genera un token, se copia, se configura en Claude y la IA puede usar `buscar-proyectos`.
- [ ] El admin puede borrar proyectos, comentarios, cuentas y gestionar catálogos.
- [ ] Funciona en móvil (360 px) y con teclado.
- [ ] Ningún texto de la interfaz queda en inglés.
- [ ] `npm run build` sin errores ni advertencias de TypeScript; la app funciona en la URL de Azure.

---

## 10. Prompt listo para pegar en una IA

```
Eres un desarrollador frontend senior. Vas a construir las interfaces del
"Repositorio de Proyectos UNAB" siguiendo EXACTAMENTE el documento FRONTEND.md
que te adjunto (stack, estructura de carpetas, contrato de API, pantallas,
diseño, seguridad y checklist).

Reglas:
- React + TypeScript + Vite dentro del repo Laravel existente (resources/js),
  React Router, TanStack Query, React Hook Form + Zod, CSS propio con los
  tokens de la sección 6. Sin Bootstrap.
- Consume solo los endpoints de la sección 3. Si necesitas algo que no está,
  no lo inventes en silencio: dime qué endpoint o campo falta en el backend.
- Toda la interfaz en español de Colombia. Correos solo @unab.edu.co.
- Cada pantalla con estados de carga, vacío, error y datos.
- Trabaja pantalla por pantalla en el orden de la sección 8. Al terminar cada
  una, dame el mensaje de commit y qué probar.

Empieza por el paso: <número y nombre del paso de la sección 8>.
```

---

## Anexo A – Fragmentos de referencia

```ts
// resources/js/api/client.ts
export class ApiError extends Error {
  constructor(public status: number, message: string, public errors?: Record<string, string[]>) {
    super(message);
  }
}

const MENSAJES: Record<number, string> = {
  401: 'Tu sesión expiró. Inicia sesión de nuevo.',
  403: 'No tienes permiso para hacer esto.',
  404: 'No encontramos lo que buscas.',
  429: 'Demasiados intentos. Espera un momento.',
  500: 'Algo falló en el servidor. Intenta más tarde.',
};

export async function api<T>(ruta: string, opciones: RequestInit = {}): Promise<T> {
  const token = localStorage.getItem('unab_token');
  const res = await fetch(`/api/v1${ruta}`, {
    ...opciones,
    headers: {
      Accept: 'application/json',
      ...(opciones.body ? { 'Content-Type': 'application/json' } : {}),
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
      ...opciones.headers,
    },
  });

  if (res.status === 204) return undefined as T;
  const cuerpo = await res.json().catch(() => ({}));

  if (!res.ok) {
    if (res.status === 401) {
      localStorage.removeItem('unab_token');
      window.dispatchEvent(new Event('unab:sesion-expirada'));
    }
    const mensaje = res.status === 422 ? cuerpo.message : (MENSAJES[res.status] ?? cuerpo.message);
    throw new ApiError(res.status, mensaje ?? 'Error inesperado', cuerpo.errors);
  }
  return cuerpo as T;
}
```

```ts
// resources/js/lib/video.ts
export function urlEmbed(url: string): string | null {
  try {
    const u = new URL(url);
    const host = u.hostname.replace(/^www\.|^m\./, '');
    if (host === 'youtu.be') return `https://www.youtube-nocookie.com/embed/${u.pathname.slice(1)}`;
    if (host === 'youtube.com') {
      const id = u.searchParams.get('v') ?? u.pathname.match(/^\/shorts\/([\w-]+)/)?.[1];
      return id ? `https://www.youtube-nocookie.com/embed/${id}` : null;
    }
    if (host === 'drive.google.com') {
      const id = u.pathname.match(/\/file\/d\/([\w-]+)/)?.[1];
      return id ? `https://drive.google.com/file/d/${id}/preview` : null;
    }
    return null;
  } catch {
    return null;
  }
}
```

## Anexo B – Si eligen Blade en lugar de React

Las pantallas, reglas, diseño y checklist son los mismos. Cambia la implementación:
- Vistas en `resources/views/` con un layout (`layouts/app.blade.php`) como en el Taller CRUD, y CSS propio en `resources/css`.
- Controladores web que **llaman a las mismas clases Action** que la API (no duplicar lógica) y autenticación por sesión con `@auth` / `@guest` y `@can('update', $proyecto)`.
- La regla `@unab.edu.co` se reutiliza con los mismos Form Requests.
- Para like y comentarios sin recargar, un poco de JavaScript con `fetch` o Alpine.js.
- Desventaja frente a React: hay dos formas de entrar (sesión web y token de API) y el frontend no demuestra la API REST.
