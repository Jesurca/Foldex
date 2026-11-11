/** Tipos copiados de los API Resources de Laravel (app/Http/Resources). */

export type Rol = 'estudiante' | 'admin';

/** UserResource */
export interface Usuario {
    id: number;
    name: string;
    email: string;
    role: Rol;
    programa: string | null;
    semestre: number | null;
    creado_en: string | null;
}

/** AutorResource: datos públicos, sin correo. */
export interface Autor {
    id: number;
    name: string;
    role: Rol;
    programa: string | null;
}

/** MateriaResource */
export interface Materia {
    id: number;
    codigo: string;
    nombre: string;
    semestre: number;
}

/** DocenteResource */
export interface Docente {
    id: number;
    nombre: string;
}

/** TecnologiaResource */
export interface Tecnologia {
    id: number;
    nombre: string;
}

/** ProyectoResource */
export interface Proyecto {
    id: number;
    slug: string;
    titulo: string;
    contexto: string;
    repositorio_url: string;
    video_url: string | null;
    periodo: string;
    materia: Materia;
    docente: Docente;
    autor: Autor;
    tecnologias: Tecnologia[];
    likes_count: number;
    comentarios_count: number;
    /** null cuando no hay sesión */
    liked: boolean | null;
    can: { delete: boolean };
    publicado_en: string | null;
}

/** ComentarioResource */
export interface Comentario {
    id: number;
    contenido: string;
    autor: Autor;
    can: { delete: boolean };
    creado_en: string | null;
}

/** Respuestas de /login y /register */
export interface RespuestaSesion {
    token: string;
    user: Usuario;
}

/** POST/DELETE /proyectos/{slug}/like */
export interface EstadoLike {
    liked: boolean;
    likes_count: number;
}

/** GET /me/tokens */
export interface TokenPersonal {
    id: number;
    nombre: string;
    ultimo_uso: string | null;
    creado_en: string | null;
    actual: boolean;
}

export interface MetaPaginacion {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
}

export interface Paginado<T> {
    data: T[];
    meta: MetaPaginacion;
}

/** Filtros de GET /proyectos (IndexProyectoRequest). */
export interface FiltrosProyectos {
    q?: string | undefined;
    materia?: number | undefined;
    docente?: number | undefined;
    tecnologia?: number | undefined;
    semestre?: number | undefined;
    periodo?: string | undefined;
    orden?: 'recientes' | 'populares' | undefined;
    page?: number | undefined;
}

/** Body de POST /proyectos (StoreProyectoRequest). */
export interface NuevoProyecto {
    titulo: string;
    contexto: string;
    materia_id: number;
    docente_id: number;
    periodo: string;
    repositorio_url: string;
    video_url: string | null;
    tecnologias: number[];
}

/** Body de POST /register (RegisterRequest). */
export interface DatosRegistro {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
    programa?: string | null;
    semestre?: number | null;
}
