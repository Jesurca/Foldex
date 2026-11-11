import { api } from './client';
import type {
    Comentario,
    DatosRegistro,
    Docente,
    EstadoLike,
    FiltrosProyectos,
    Materia,
    NuevoProyecto,
    Paginado,
    Proyecto,
    RespuestaSesion,
    Tecnologia,
    TokenPersonal,
    Usuario,
} from './tipos';

/** Laravel envuelve los Resources en `{ data }`; esto lo desenvuelve si viene así. */
export function desenvolver<T>(respuesta: T | { data: T }): T {
    if (respuesta && typeof respuesta === 'object' && 'data' in respuesta) {
        return (respuesta as { data: T }).data;
    }
    return respuesta as T;
}

/** Normaliza una respuesta paginada `{ data, meta }` (o una lista simple) a `Paginado<T>`. */
export function paginado<T>(respuesta: Paginado<T> | T[] | { data: T[] }): Paginado<T> {
    if (Array.isArray(respuesta)) {
        return { data: respuesta, meta: { current_page: 1, last_page: 1, per_page: respuesta.length, total: respuesta.length, from: 1, to: respuesta.length } };
    }
    if ('meta' in respuesta) return respuesta;
    return paginado(respuesta.data);
}

const enc = encodeURIComponent;

/** POST /proyectos va como multipart porque puede llevar la foto de portada. */
export function formularioProyecto(datos: NuevoProyecto): FormData {
    const f = new FormData();
    f.append('titulo', datos.titulo);
    f.append('contexto', datos.contexto);
    f.append('materia_id', String(datos.materia_id));
    f.append('docente_id', String(datos.docente_id));
    f.append('periodo', datos.periodo);
    f.append('repositorio_url', datos.repositorio_url);
    if (datos.video_url) f.append('video_url', datos.video_url);
    for (const id of datos.tecnologias) f.append('tecnologias[]', String(id));
    if (datos.portada) f.append('portada', datos.portada);
    return f;
}

export const auth = {
    login: (email: string, password: string) =>
        api<RespuestaSesion>('login', { method: 'POST', body: { email, password, device_name: 'web' } }),
    registro: (datos: DatosRegistro) =>
        api<RespuestaSesion>('register', { method: 'POST', body: { ...datos, device_name: 'web' } }),
    me: async () => desenvolver(await api<{ data: Usuario }>('me')),
    logout: () => api<void>('logout', { method: 'POST' }),
};

export const catalogos = {
    materias: async () => desenvolver(await api<{ data: Materia[] }>('materias')),
    docentes: async () => desenvolver(await api<{ data: Docente[] }>('docentes')),
    tecnologias: async () => desenvolver(await api<{ data: Tecnologia[] }>('tecnologias')),
};

export const proyectos = {
    listar: async (filtros: FiltrosProyectos = {}) =>
        paginado(await api<Paginado<Proyecto>>('proyectos', { query: { ...filtros } })),
    detalle: async (slug: string) => desenvolver(await api<{ data: Proyecto }>(`proyectos/${enc(slug)}`)),
    publicar: async (datos: NuevoProyecto) =>
        desenvolver(await api<{ data: Proyecto }>('proyectos', { method: 'POST', body: formularioProyecto(datos) })),
    eliminar: (slug: string) => api<void>(`proyectos/${enc(slug)}`, { method: 'DELETE' }),
};

export const likes = {
    dar: (slug: string) => api<EstadoLike>(`proyectos/${enc(slug)}/like`, { method: 'POST' }),
    quitar: (slug: string) => api<EstadoLike>(`proyectos/${enc(slug)}/like`, { method: 'DELETE' }),
};

export const comentarios = {
    listar: async (slug: string, page = 1) =>
        paginado(await api<Paginado<Comentario>>(`proyectos/${enc(slug)}/comentarios`, { query: { page } })),
    crear: async (slug: string, contenido: string) =>
        desenvolver(await api<{ data: Comentario }>(`proyectos/${enc(slug)}/comentarios`, { method: 'POST', body: { contenido } })),
    eliminar: (id: number) => api<void>(`comentarios/${id}`, { method: 'DELETE' }),
};

export const perfil = {
    proyectos: async (page = 1) => paginado(await api<Paginado<Proyecto>>('me/proyectos', { query: { page } })),
    likes: async (page = 1) => paginado(await api<Paginado<Proyecto>>('me/likes', { query: { page } })),
    tokens: async () => desenvolver(await api<{ data: TokenPersonal[] }>('me/tokens')),
    crearToken: (nombre: string) => api<{ token: string; aviso: string }>('me/tokens', { method: 'POST', body: { nombre } }),
    eliminarToken: (id: number) => api<void>(`me/tokens/${id}`, { method: 'DELETE' }),
};

export const admin = {
    proyectos: async (page = 1, q = '') => paginado(await api<Paginado<Proyecto>>('admin/proyectos', { query: { page, q } })),
    eliminarProyecto: (slug: string) => api<void>(`admin/proyectos/${enc(slug)}`, { method: 'DELETE' }),
    usuarios: async (page = 1, q = '') => paginado(await api<Paginado<Usuario>>('admin/users', { query: { page, q } })),
    eliminarUsuario: (id: number) => api<void>(`admin/users/${id}`, { method: 'DELETE' }),
};
