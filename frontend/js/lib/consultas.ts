// Consultas de React Query reutilizables: catálogos cacheados y "me gusta" optimista.

// Imports: React Query, avisos, cliente y servicios de la API y la sesión.
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { toast } from 'sonner';
import { ApiError, mensajeDeError } from './api/client';
import { catalogos, likes } from './api/servicios';
import type { Paginado, Proyecto } from './api/tipos';
import { useAuth } from './auth';

/** Los catálogos casi no cambian: se piden una vez por sesión de navegador. */
const CATALOGO = { staleTime: Infinity, gcTime: Infinity } as const;

export const useMaterias = () => useQuery({ queryKey: ['catalogo', 'materias'], queryFn: catalogos.materias, ...CATALOGO });
export const useDocentes = () => useQuery({ queryKey: ['catalogo', 'docentes'], queryFn: catalogos.docentes, ...CATALOGO });
export const useTecnologias = () => useQuery({ queryKey: ['catalogo', 'tecnologias'], queryFn: catalogos.tecnologias, ...CATALOGO });

type Cache = Proyecto | Paginado<Proyecto> | undefined;

function aplicarLike(dato: Cache, slug: string, liked: boolean, likesCount?: number): Cache {
    const cambiar = (p: Proyecto): Proyecto =>
        p.slug !== slug ? p : { ...p, liked, likes_count: likesCount ?? Math.max(0, p.likes_count + (liked ? 1 : -1)) };
    if (!dato) return dato;
    if ('data' in dato && Array.isArray(dato.data)) return { ...dato, data: dato.data.map(cambiar) };
    return cambiar(dato as Proyecto);
}

/**
 * Like optimista: actualiza todas las listas y el detalle al instante y, cuando
 * responde la API, usa su `{liked, likes_count}` como valor final. Si falla, revierte.
 */
export function useLike() {
    const queryClient = useQueryClient();
    const { usuario, abrirModal } = useAuth();

    const mutacion = useMutation({
        mutationFn: ({ slug, liked }: { slug: string; liked: boolean }) => (liked ? likes.dar(slug) : likes.quitar(slug)),
        onMutate: async ({ slug, liked }) => {
            await queryClient.cancelQueries({ queryKey: ['proyectos'] });
            const previo = queryClient.getQueriesData<Cache>({ queryKey: ['proyectos'] });
            queryClient.setQueriesData<Cache>({ queryKey: ['proyectos'] }, (d) => aplicarLike(d, slug, liked));
            return { previo };
        },
        onSuccess: (estado, { slug }) => {
            queryClient.setQueriesData<Cache>({ queryKey: ['proyectos'] }, (d) => aplicarLike(d, slug, estado.liked, estado.likes_count));
            void queryClient.invalidateQueries({ queryKey: ['proyectos', 'mis-likes'] });
        },
        onError: (error, _vars, ctx) => {
            ctx?.previo.forEach(([clave, dato]) => queryClient.setQueryData(clave, dato));
            if (!(error instanceof ApiError && error.status === 401)) toast.error(mensajeDeError(error));
        },
    });

    return (proyecto: Pick<Proyecto, 'slug' | 'liked'>) => {
        if (!usuario) {
            toast('Ingresa para dar me gusta');
            abrirModal('entrar');
            return;
        }
        mutacion.mutate({ slug: proyecto.slug, liked: !proyecto.liked });
    };
}
