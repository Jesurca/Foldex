/**
 * Cliente HTTP para /api/v1. Usa token Bearer de Sanctum guardado en localStorage
 * (clave `unab_token`). La SPA y la API comparten origen, así que la URL base es relativa.
 */
export const API_URL: string = import.meta.env.VITE_API_URL || '/api/v1';
export const TOKEN_KEY = 'unab_token';
export const EVENTO_SESION_EXPIRADA = 'unab:sesion-expirada';

export type ErroresValidacion = Record<string, string[]>;

// Error de la API con su código HTTP y los errores 422 por campo.
export class ApiError extends Error {
    readonly status: number;
    readonly errors: ErroresValidacion;

    constructor(status: number, message: string, errors: ErroresValidacion = {}) {
        super(message);
        this.name = 'ApiError';
        this.status = status;
        this.errors = errors;
    }

    /** Primer mensaje de error de un campo (para mostrarlo debajo del input). */
    campo(nombre: string): string | undefined {
        return this.errors[nombre]?.[0];
    }
}

// Mensajes en español por código HTTP.
const MENSAJES: Record<number, string> = {
    401: 'Tu sesión no es válida. Ingresa de nuevo.',
    403: 'No tienes permiso para realizar esta acción.',
    404: 'No encontramos lo que buscas.',
    422: 'Revisa los datos del formulario.',
    429: 'Demasiadas solicitudes. Espera un momento e intenta de nuevo.',
    500: 'Ocurrió un error en el servidor. Intenta más tarde.',
};

const enNavegador = typeof window !== 'undefined';

// Lectura y escritura del token en localStorage.
export const token = {
    get(): string | null {
        if (!enNavegador) return null;
        try {
            return window.localStorage.getItem(TOKEN_KEY);
        } catch {
            return null;
        }
    },
    set(valor: string): void {
        try {
            window.localStorage.setItem(TOKEN_KEY, valor);
        } catch {
            /* almacenamiento bloqueado: la sesión dura lo que dure la pestaña */
        }
    },
    clear(): void {
        try {
            window.localStorage.removeItem(TOKEN_KEY);
        } catch {
            /* nada que limpiar */
        }
    },
};

type Query = Record<string, string | number | boolean | null | undefined>;

export interface OpcionesApi {
    method?: 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE';
    body?: unknown;
    query?: Query;
    signal?: AbortSignal;
}

export function construirUrl(ruta: string, query?: Query): string {
    const params = new URLSearchParams();
    for (const [clave, valor] of Object.entries(query ?? {})) {
        if (valor !== undefined && valor !== null && valor !== '') params.set(clave, String(valor));
    }
    const qs = params.toString();
    return `${API_URL.replace(/\/$/, '')}/${ruta.replace(/^\//, '')}${qs ? `?${qs}` : ''}`;
}

// fetch hacia /api/v1 con el token Bearer; convierte errores en ApiError.
export async function api<T>(ruta: string, opciones: OpcionesApi = {}): Promise<T> {
    const { method = 'GET', body, query, signal } = opciones;
    const actual = token.get();
    // Con FormData (archivos) el navegador pone el Content-Type multipart con su `boundary`: no hay que fijarlo.
    const esFormulario = typeof FormData !== 'undefined' && body instanceof FormData;
    const headers: Record<string, string> = { Accept: 'application/json' };
    if (body !== undefined && !esFormulario) headers['Content-Type'] = 'application/json';
    if (actual) headers['Authorization'] = `Bearer ${actual}`;

    let respuesta: Response;
    try {
        respuesta = await fetch(construirUrl(ruta, query), {
            method,
            headers,
            ...(body !== undefined ? { body: esFormulario ? body : JSON.stringify(body) } : {}),
            ...(signal ? { signal } : {}),
        });
    } catch (error) {
        if (error instanceof DOMException && error.name === 'AbortError') throw error;
        throw new ApiError(0, 'No pudimos conectar con el servidor. Revisa tu conexión.');
    }

    if (respuesta.status === 204) return undefined as T;

    const datos: unknown = await respuesta.json().catch(() => null);

    if (!respuesta.ok) {
        const cuerpo = (datos ?? {}) as { message?: string; errors?: ErroresValidacion };
        if (respuesta.status === 401 && actual) {
            token.clear();
            if (enNavegador) window.dispatchEvent(new CustomEvent(EVENTO_SESION_EXPIRADA));
        }
        const mensaje =
            respuesta.status === 422 || respuesta.status === 403
                ? cuerpo.message || MENSAJES[respuesta.status]
                : MENSAJES[respuesta.status] || cuerpo.message || MENSAJES[500];
        throw new ApiError(respuesta.status, mensaje ?? 'Error inesperado.', cuerpo.errors ?? {});
    }

    return datos as T;
}

/** Mensaje legible de cualquier error (para toasts y estados de error). */
export function mensajeDeError(error: unknown): string {
    if (error instanceof ApiError) return error.message;
    return 'Ocurrió un error inesperado.';
}
