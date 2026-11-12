// Ruta /publicar: un estudiante (o admin) publica un proyecto con el formulario o desde Claude por MCP.

// Imports: router, React Query (refrescar listas), avisos y componentes propios.
import { useQueryClient } from '@tanstack/react-query';
import { createFileRoute, Link, useNavigate } from '@tanstack/react-router';
import { ArrowLeft, Bot } from 'lucide-react';
import { toast } from 'sonner';
import { Cargando } from '@/components/Estados';
import { BotonConectarMcp } from '@/components/ConectarMcp';
import { FormularioProyecto } from '@/components/FormularioProyecto';
import { EmptyAccount, ProjectShell } from '@/components/ProjectShell';
import { proyectos } from '@/lib/api/servicios';
import { useAuth } from '@/lib/auth';
import { metadata } from '@/lib/formato';

// Registro de la ruta en TanStack Router (el archivo define la URL).
export const Route = createFileRoute('/publicar')({
    head: () => metadata('Publica tu proyecto', 'Comparte un proyecto académico con la comunidad de ingeniería.'),
    component: Publicar,
});

/** Exige sesión: sin usuario muestra la invitación a ingresar. */
function Publicar() {
    const { usuario, cargando } = useAuth();
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
    return <Pagina nombre={usuario.name} email={usuario.email} />;
}

function Pagina({ nombre, email }: { nombre: string; email: string }) {
    const navigate = useNavigate();
    const queryClient = useQueryClient();

    return (
        <ProjectShell>
            <main className="site-width max-w-3xl py-10">
                <Link to="/" className="flex items-center gap-2 text-sm text-muted-foreground">
                    <ArrowLeft className="size-4" />
                    Volver a la biblioteca
                </Link>
                <h1 className="mt-8 text-3xl font-bold">Publicar proyecto</h1>
                <p className="mt-3 text-sm text-muted-foreground">
                    {nombre} · {email}
                </p>

                {/* Atajo: publicar desde Claude con MCP */}
                <div className="mt-6 flex flex-wrap items-center justify-between gap-4 rounded-lg border p-5">
                    <div className="flex max-w-md gap-3">
                        <Bot className="mt-0.5 size-5 shrink-0 text-primary" />
                        <p className="text-sm text-muted-foreground">
                            <strong className="text-foreground">¿Prefieres que Claude lo haga?</strong> Conéctalo una vez y publica con un mensaje: tú das el repositorio y el docente, Claude
                            completa el resto.
                        </p>
                    </div>
                    <BotonConectarMcp variante="outline" />
                </div>

                {/* Formulario compartido con la edición del admin */}
                <FormularioProyecto
                    textoBoton="Publicar proyecto"
                    guardar={proyectos.publicar}
                    alGuardar={(p) => {
                        toast.success('¡Proyecto publicado!');
                        void queryClient.invalidateQueries({ queryKey: ['proyectos'] });
                        queryClient.setQueryData(['proyectos', 'detalle', p.slug], p);
                        void navigate({ to: '/proyectos/$id', params: { id: p.slug } });
                    }}
                />
            </main>
        </ProjectShell>
    );
}
