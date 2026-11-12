// Ruta /editar/:slug — solo el admin edita proyectos (la API lo valida con ProyectoPolicy::update).

// Imports: router, React Query (cargar y refrescar), avisos y componentes propios.
import { useQuery, useQueryClient } from '@tanstack/react-query';
import { createFileRoute, Link, useNavigate } from '@tanstack/react-router';
import { ArrowLeft, ShieldAlert } from 'lucide-react';
import { toast } from 'sonner';
import { Cargando, ErrorEstado } from '@/components/Estados';
import { FormularioProyecto } from '@/components/FormularioProyecto';
import { EmptyAccount, ProjectShell } from '@/components/ProjectShell';
import { proyectos } from '@/lib/api/servicios';
import { useAuth } from '@/lib/auth';
import { metadata } from '@/lib/formato';

// Registro de la ruta: el parámetro `$id` es el slug del proyecto.
export const Route = createFileRoute('/editar/$id')({
    head: () => metadata('Editar proyecto', 'Moderación de publicaciones del repositorio.'),
    component: Editar,
});

function Editar() {
    const { id: slug } = Route.useParams();
    const { usuario, cargando, esAdmin } = useAuth();
    const navigate = useNavigate();
    const queryClient = useQueryClient();
    const consulta = useQuery({ queryKey: ['proyectos', 'detalle', slug], queryFn: () => proyectos.detalle(slug), enabled: esAdmin });

    // --- Acceso: sesión y rol admin ---
    if (cargando) {
        return (
            <ProjectShell>
                <Cargando />
            </ProjectShell>
        );
    }
    if (!usuario) {
        return (
            <ProjectShell>
                <EmptyAccount />
            </ProjectShell>
        );
    }
    if (!esAdmin) {
        return (
            <ProjectShell>
                <main className="site-width py-20 text-center">
                    <ShieldAlert className="mx-auto size-12 text-primary" />
                    <h1 className="mt-5 text-2xl font-bold">Solo el administrador puede editar proyectos</h1>
                    <p className="mt-2 text-muted-foreground">Si quieres cambiar tu publicación, elimínala y vuelve a publicarla.</p>
                </main>
            </ProjectShell>
        );
    }

    // --- Formulario precargado con el proyecto ---
    return (
        <ProjectShell>
            <main className="site-width max-w-3xl py-10">
                <Link to="/proyectos/$id" params={{ id: slug }} className="flex items-center gap-2 text-sm text-muted-foreground">
                    <ArrowLeft className="size-4" />
                    Volver al proyecto
                </Link>
                <h1 className="mt-8 text-3xl font-bold">Editar proyecto</h1>
                {consulta.isPending ? (
                    <Cargando />
                ) : consulta.isError ? (
                    <ErrorEstado error={consulta.error} reintentar={() => void consulta.refetch()} />
                ) : (
                    <>
                        <p className="mt-3 text-sm text-muted-foreground">
                            Publicado por {consulta.data.autor.name}
                        </p>
                        <FormularioProyecto
                            inicial={consulta.data}
                            textoBoton="Guardar cambios"
                            guardar={(datos) => proyectos.actualizar(slug, datos)}
                            alGuardar={(p) => {
                                toast.success('Proyecto actualizado');
                                void queryClient.invalidateQueries({ queryKey: ['proyectos'] });
                                queryClient.setQueryData(['proyectos', 'detalle', p.slug], p);
                                void navigate({ to: '/proyectos/$id', params: { id: p.slug } });
                            }}
                        />
                    </>
                )}
            </main>
        </ProjectShell>
    );
}
