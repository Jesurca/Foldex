// Sesión del usuario: AuthProvider guarda quién está conectado y useAuth() lo expone a cualquier pantalla.

// Imports: React Query, React, avisos y el cliente/servicios de la API.
import { useQuery, useQueryClient } from '@tanstack/react-query';
import { createContext, useCallback, useContext, useEffect, useMemo, useState, type ReactNode } from 'react';
import { toast } from 'sonner';
import { ApiError, EVENTO_SESION_EXPIRADA, token } from './api/client';
import { auth } from './api/servicios';
import type { DatosRegistro, Usuario } from './api/tipos';

/** Debe coincidir con ALLOWED_EMAIL_DOMAIN del backend (que es quien realmente valida). */
export const DOMINIO_PERMITIDO = 'unab.edu.co';
export const CLAVE_SESION = ['sesion'] as const;

export function normalizarCorreo(correo: string): string {
    return correo.trim().toLowerCase();
}

export function correoPermitido(correo: string): boolean {
    return normalizarCorreo(correo).endsWith(`@${DOMINIO_PERMITIDO}`);
}

type ModoModal = 'entrar' | 'registro';

interface AuthContexto {
    usuario: Usuario | null;
    cargando: boolean;
    esAdmin: boolean;
    entrar: (email: string, password: string) => Promise<Usuario>;
    registrarse: (datos: DatosRegistro) => Promise<Usuario>;
    salir: () => Promise<void>;
    modal: { abierto: boolean; modo: ModoModal };
    abrirModal: (modo?: ModoModal) => void;
    cerrarModal: () => void;
    cambiarModo: (modo: ModoModal) => void;
}

const Contexto = createContext<AuthContexto | null>(null);

// Proveedor de sesión: carga /me, maneja login/registro/salida y el modal de acceso.
export function AuthProvider({ children }: { children: ReactNode }) {
    const queryClient = useQueryClient();
    const [modal, setModal] = useState<{ abierto: boolean; modo: ModoModal }>({ abierto: false, modo: 'entrar' });

    const sesion = useQuery({
        queryKey: CLAVE_SESION,
        queryFn: async (): Promise<Usuario | null> => {
            if (!token.get()) return null;
            try {
                return await auth.me();
            } catch (error) {
                if (error instanceof ApiError && error.status === 401) return null;
                throw error;
            }
        },
        staleTime: 5 * 60_000,
        retry: false,
    });

    /** Tras entrar o salir, todo lo que depende del usuario (liked, can.delete, mi espacio) se vuelve a pedir. */
    const refrescarDatos = useCallback(() => {
        queryClient.removeQueries({ queryKey: ['proyectos', 'mios'] });
        queryClient.removeQueries({ queryKey: ['proyectos', 'mis-likes'] });
        queryClient.removeQueries({ queryKey: ['admin'] });
        void queryClient.invalidateQueries({ predicate: (q) => q.queryKey[0] !== CLAVE_SESION[0] });
    }, [queryClient]);

    const iniciar = useCallback(
        (nuevoToken: string, usuario: Usuario) => {
            token.set(nuevoToken);
            queryClient.setQueryData(CLAVE_SESION, usuario);
            refrescarDatos();
            return usuario;
        },
        [queryClient, refrescarDatos],
    );

    const entrar = useCallback(
        async (email: string, password: string) => {
            const r = await auth.login(normalizarCorreo(email), password);
            return iniciar(r.token, r.user);
        },
        [iniciar],
    );

    const registrarse = useCallback(
        async (datos: DatosRegistro) => {
            const r = await auth.registro({ ...datos, email: normalizarCorreo(datos.email) });
            return iniciar(r.token, r.user);
        },
        [iniciar],
    );

    const salir = useCallback(async () => {
        try {
            await auth.logout();
        } catch {
            /* si el token ya no sirve, igual cerramos la sesión local */
        }
        token.clear();
        queryClient.setQueryData(CLAVE_SESION, null);
        refrescarDatos();
    }, [queryClient, refrescarDatos]);

    useEffect(() => {
        const alExpirar = () => {
            if (queryClient.getQueryData(CLAVE_SESION)) toast.error('Tu sesión expiró. Ingresa de nuevo.');
            queryClient.setQueryData(CLAVE_SESION, null);
            refrescarDatos();
        };
        window.addEventListener(EVENTO_SESION_EXPIRADA, alExpirar);
        return () => window.removeEventListener(EVENTO_SESION_EXPIRADA, alExpirar);
    }, [queryClient, refrescarDatos]);

    const usuario = sesion.data ?? null;

    const valor = useMemo<AuthContexto>(
        () => ({
            usuario,
            cargando: sesion.isPending,
            esAdmin: usuario?.role === 'admin',
            entrar,
            registrarse,
            salir,
            modal,
            abrirModal: (modo = 'entrar') => setModal({ abierto: true, modo }),
            cerrarModal: () => setModal((m) => ({ ...m, abierto: false })),
            cambiarModo: (modo) => setModal({ abierto: true, modo }),
        }),
        [usuario, sesion.isPending, entrar, registrarse, salir, modal],
    );

    return <Contexto.Provider value={valor}>{children}</Contexto.Provider>;
}

// Hook para leer la sesión desde cualquier componente.
export function useAuth(): AuthContexto {
    const ctx = useContext(Contexto);
    if (!ctx) throw new Error('useAuth debe usarse dentro de <AuthProvider>');
    return ctx;
}
