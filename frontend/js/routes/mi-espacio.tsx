// Ruta /mi-espacio: mis publicaciones y los proyectos que me gustan (GET /api/v1/me/proyectos y /me/likes).

// Imports: React Query, router, iconos, componentes propios, servicios y sesión.
import { keepPreviousData, useQuery } from '@tanstack/react-query';
import { createFileRoute, Link, useNavigate } from '@tanstack/react-router';
import { FolderOpen, Heart, Plus } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Cargando, ErrorEstado, Paginacion, Vacio } from '@/components/Estados';
import { ProjectCard } from '@/components/ProjectCard';
import { EmptyAccount, ProjectShell } from '@/components/ProjectShell';
import { perfil } from '@/lib/api/servicios';
import type { Usuario } from '@/lib/api/tipos';
import { useAuth } from '@/lib/auth';
import { metadata } from '@/lib/formato';

type Pestana = 'mios' | 'likes';

// Registro de la ruta /mi-espacio (la pestaña activa va en la URL).
export const Route = createFileRoute('/mi-espacio')({
    validateSearch: (s: Record<string, unknown>): { tab?: 'likes' | undefined; page?: number | undefined } => {
        const page = Number(s['page']);
        return { tab: s['tab'] === 'likes' ? 'likes' : undefined, page: Number.isInteger(page) && page > 1 ? page : undefined };
    },
    head: () => metadata('Mi espacio', 'Tu perfil, tus publicaciones y los proyectos que te gustan.'),
    component: MiEspacio,
});

// Exige sesión.
function MiEspacio() {
    const { usuario, cargando } = useAuth();
    return (
        <ProjectShell>
            {cargando ? <Cargando /> : usuario ? <Contenido usuario={usuario} /> : <EmptyAccount />}
        </ProjectShell>
    );
}

// Pestañas con las dos listas paginadas.
function Contenido({ usuario }: { usuario: Usuario }) {
    const { tab, page = 1 } = Route.useSearch();
    const navigate = useNavigate({ from: '/mi-espacio' });
    const pestana: Pestana = tab === 'likes' ? 'likes' : 'mios';

    const mios = useQuery({
        queryKey: ['proyectos', 'mios', pestana === 'mios' ? page : 1],
        queryFn: () => perfil.proyectos(pestana === 'mios' ? page : 1),
        placeholderData: keepPreviousData,
    });
    const meGusta = useQuery({
        queryKey: ['proyectos', 'mis-likes', pestana === 'likes' ? page : 1],
        queryFn: () => perfil.likes(pestana === 'likes' ? page : 1),
        placeholderData: keepPreviousData,
    });
    const actual = pestana === 'mios' ? mios : meGusta;

    return (
        <main className="site-width py-12">
            <div className="flex flex-wrap items-center justify-between gap-5">
                <div className="flex items-center gap-4">
                    <span className="avatar-mini size-16 text-2xl">{usuario.name.slice(0, 1)}</span>
                    <div>
                        <h1 className="text-3xl font-bold">{usuario.name}</h1>
                        <p className="mt-1 text-sm text-muted-foreground">
                            {usuario.email} · {usuario.role === 'admin' ? 'Administrador' : 'Estudiante'}
                            {usuario.semestre ? ` · Semestre ${usuario.semestre}` : ''}
                        </p>
                    </div>
                </div>
                <Button asChild>
                    <Link to="/publicar">
                        <Plus />
                        Nuevo proyecto
                    </Link>
                </Button>
            </div>
            <div className="mb-7 mt-10 flex gap-3 border-b pb-4">
                <Button variant={pestana === 'mios' ? 'secondary' : 'ghost'} onClick={() => void navigate({ search: {} })}>
                    <FolderOpen />
                    Mis publicaciones ({mios.data?.meta.total ?? '…'})
                </Button>
                <Button variant={pestana === 'likes' ? 'secondary' : 'ghost'} onClick={() => void navigate({ search: { tab: 'likes' } })}>
                    <Heart />
                    Me gusta ({meGusta.data?.meta.total ?? '…'})
                </Button>
            </div>
            {actual.isPending ? (
                <Cargando />
            ) : actual.isError ? (
                <ErrorEstado error={actual.error} reintentar={() => void actual.refetch()} />
            ) : actual.data.data.length ? (
                <>
                    <div className={`grid gap-6 sm:grid-cols-2 lg:grid-cols-3 ${actual.isPlaceholderData ? 'opacity-60' : ''}`}>
                        {actual.data.data.map((p) => (
                            <ProjectCard key={p.id} project={p} />
                        ))}
                    </div>
                    <Paginacion
                        meta={actual.data.meta}
                        alCambiar={(n) => void navigate({ search: (prev) => ({ ...prev, page: n > 1 ? n : undefined }) })}
                    />
                </>
            ) : (
                <Vacio>{pestana === 'mios' ? 'Tu próxima idea puede empezar aquí. Publica tu primer proyecto.' : 'Aún no tienes proyectos favoritos.'}</Vacio>
            )}
        </main>
    );
}
