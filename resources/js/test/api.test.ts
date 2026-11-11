import { afterEach, describe, expect, it, vi } from 'vitest';
import { api, ApiError, construirUrl, EVENTO_SESION_EXPIRADA, token } from '@/lib/api/client';
import { desenvolver, paginado } from '@/lib/api/servicios';
import { correoPermitido, normalizarCorreo } from '@/lib/auth';
import { urlVideoEmbebido } from '@/lib/formato';

function respuesta(status: number, cuerpo?: unknown) {
    return Promise.resolve(new Response(cuerpo === undefined ? null : JSON.stringify(cuerpo), { status, headers: { 'Content-Type': 'application/json' } }));
}

afterEach(() => {
    vi.restoreAllMocks();
    token.clear();
});

describe('cliente API', () => {
    it('arma la URL con la base /api/v1 y omite filtros vacíos', () => {
        expect(construirUrl('proyectos', { q: 'robot', materia: undefined, page: 2, docente: '' })).toBe('/api/v1/proyectos?q=robot&page=2');
    });

    it('envía Accept y el token Bearer guardado', async () => {
        token.set('abc');
        const fetchMock = vi.spyOn(globalThis, 'fetch').mockImplementation(() => respuesta(200, { data: { id: 1 } }));

        await api('me');

        const [, init] = fetchMock.mock.calls[0]!;
        expect(init?.headers).toMatchObject({ Accept: 'application/json', Authorization: 'Bearer abc' });
    });

    it('convierte un 422 en ApiError con errores por campo', async () => {
        vi.spyOn(globalThis, 'fetch').mockImplementation(() =>
            respuesta(422, { message: 'Solo se permiten correos institucionales @unab.edu.co', errors: { email: ['Solo se permiten correos institucionales @unab.edu.co'] } }),
        );

        const error = await api('register', { method: 'POST', body: {} }).catch((e: unknown) => e);
        expect(error).toBeInstanceOf(ApiError);
        expect((error as ApiError).status).toBe(422);
        expect((error as ApiError).campo('email')).toContain('@unab.edu.co');
    });

    it('ante un 401 con sesión borra el token y emite unab:sesion-expirada', async () => {
        token.set('vencido');
        const escucha = vi.fn();
        window.addEventListener(EVENTO_SESION_EXPIRADA, escucha);
        vi.spyOn(globalThis, 'fetch').mockImplementation(() => respuesta(401, { message: 'No autenticado.' }));

        await expect(api('me')).rejects.toMatchObject({ status: 401, message: 'Tu sesión no es válida. Ingresa de nuevo.' });
        expect(token.get()).toBeNull();
        expect(escucha).toHaveBeenCalledOnce();
        window.removeEventListener(EVENTO_SESION_EXPIRADA, escucha);
    });

    it('si falla la red responde en español', async () => {
        vi.spyOn(globalThis, 'fetch').mockRejectedValue(new TypeError('Failed to fetch'));

        await expect(api('ping')).rejects.toMatchObject({ status: 0, message: expect.stringContaining('No pudimos conectar con el servidor') });
    });

    it('devuelve undefined en 204', async () => {
        vi.spyOn(globalThis, 'fetch').mockImplementation(() => respuesta(204));
        await expect(api('logout', { method: 'POST' })).resolves.toBeUndefined();
    });

    it('acepta respuestas envueltas en { data } y paginadas { data, meta }', () => {
        expect(desenvolver({ data: [1, 2] })).toEqual([1, 2]);
        expect(paginado([1, 2]).meta.total).toBe(2);
        const meta = { current_page: 1, last_page: 3, per_page: 12, total: 30, from: 1, to: 12 };
        expect(paginado({ data: [1], meta }).meta.last_page).toBe(3);
    });
});

describe('correo institucional', () => {
    it('normaliza y valida el dominio', () => {
        expect(normalizarCorreo('  Ana@UNAB.edu.co ')).toBe('ana@unab.edu.co');
        expect(correoPermitido('Ana@UNAB.edu.co')).toBe(true);
        expect(correoPermitido('ana@gmail.com')).toBe(false);
        expect(correoPermitido('ana@unab.edu.co.fake.com')).toBe(false);
        expect(correoPermitido('ana@falsounab.edu.co')).toBe(false);
    });
});

describe('video embebido', () => {
    it('acepta YouTube y Drive', () => {
        expect(urlVideoEmbebido('https://www.youtube.com/watch?v=dQw4w9WgXcQ')).toBe('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ');
        expect(urlVideoEmbebido('https://youtu.be/dQw4w9WgXcQ')).toBe('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ');
        expect(urlVideoEmbebido('https://drive.google.com/file/d/1AbCdEfGhIjKlMn/view')).toBe('https://drive.google.com/file/d/1AbCdEfGhIjKlMn/preview');
    });

    it('cualquier otro enlace no se incrusta', () => {
        expect(urlVideoEmbebido('https://vimeo.com/123')).toBeNull();
        expect(urlVideoEmbebido('http://www.youtube.com/watch?v=dQw4w9WgXcQ')).toBeNull();
        expect(urlVideoEmbebido('javascript:alert(1)')).toBeNull();
        expect(urlVideoEmbebido(null)).toBeNull();
    });
});
