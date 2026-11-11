import { keepPreviousData, useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { createFileRoute, Link } from '@tanstack/react-router';
import { useEffect, useState } from 'react';
import { Search, ShieldCheck, Trash2 } from 'lucide-react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import { Cargando, ErrorEstado, Paginacion, Vacio } from '@/components/Estados';
import { ProjectShell } from '@/components/ProjectShell';
import { mensajeDeError } from '@/lib/api/client';
import { admin } from '@/lib/api/servicios';
import type { Usuario } from '@/lib/api/tipos';
import { useAuth } from '@/lib/auth';
import { fecha, metadata } from '@/lib/formato';

export const Route = createFileRoute('/administracion')({
    head: () => metadata('Administración', 'Moderación de publicaciones y cuentas.'),
    component: Administracion,
});

function Administracion() {
    const { usuario, esAdmin, cargando, abrirModal } = useAuth();

    return (
        <ProjectShell>
            <main className="site-width py-12">
                <ShieldCheck className="size-8 text-primary" />
                <h1 className="mt-3 text-3xl font-bold">Administración</h1>
                {cargando ? (
                    <Cargando />
                ) : !usuario ? (
                    <p className="mt-5 text-muted-foreground">
                        <button className="font-semibold text-primary underline-offset-4 hover:underline" onClick={() => abrirModal('entrar')}>
                            Ingresa
                        </button>{' '}
                        con una cuenta de administrador para ver esta sección.
                    </p>
                ) : !esAdmin ? (
                    <p className="mt-5 text-muted-foreground">Esta sección es solo para administradores.</p>
                ) : (
                    <>
                        <p className="mt-3 text-sm text-muted-foreground">Las eliminaciones son definitivas desde la aplicación (en la base de datos quedan como borrado lógico).</p>
                        <Publicaciones />
                        <Cuentas yo={usuario} />
                    </>
                )}
            </main>
        </ProjectShell>
    );
}

function useBusqueda() {
    const [texto, setTexto] = useState('');
    const [q, setQ] = useState('');
    useEffect(() => {
        const t = setTimeout(() => setQ(texto.trim()), 400);
        return () => clearTimeout(t);
    }, [texto]);
    return { texto, setTexto, q };
}

function Buscador({ valor, alCambiar, etiqueta }: { valor: string; alCambiar: (v: string) => void; etiqueta: string }) {
    return (
        <div className="relative mb-4 max-w-sm">
            <Search className="absolute left-4 top-3.5 size-4 text-muted-foreground" />
            <input aria-label={etiqueta} placeholder={etiqueta} value={valor} maxLength={100} onChange={(e) => alCambiar(e.target.value)} className="field pl-11" />
        </div>
    );
}

function Publicaciones() {
    const queryClient = useQueryClient();
    const { texto, setTexto, q } = useBusqueda();
    const [pagina, setPagina] = useState(1);
    useEffect(() => setPagina(1), [q]);

    const lista = useQuery({
        queryKey: ['admin', 'proyectos', pagina, q],
        queryFn: () => admin.proyectos(pagina, q),
        placeholderData: keepPreviousData,
    });

    const eliminar = useMutation({
        mutationFn: (slug: string) => admin.eliminarProyecto(slug),
        onSuccess: () => {
            toast.success('Proyecto eliminado');
            void queryClient.invalidateQueries({ queryKey: ['admin'] });
            void queryClient.invalidateQueries({ queryKey: ['proyectos'] });
        },
        onError: (e) => toast.error(mensajeDeError(e)),
    });

    return (
        <section>
            <h2 className="mb-4 mt-9 text-xl font-bold">Publicaciones ({lista.data?.meta.total ?? '…'})</h2>
            <Buscador valor={texto} alCambiar={setTexto} etiqueta="Buscar proyectos" />
            {lista.isPending ? (
                <Cargando />
            ) : lista.isError ? (
                <ErrorEstado error={lista.error} reintentar={() => void lista.refetch()} />
            ) : !lista.data.data.length ? (
                <Vacio>No hay publicaciones{q ? ' con esa búsqueda' : ''}.</Vacio>
            ) : (
                <>
                    {lista.data.data.map((p) => (
                        <div className="flex items-center justify-between gap-4 border-b py-4" key={p.id}>
                            <div className="min-w-0">
                                <Link to="/proyectos/$id" params={{ id: p.slug }} className="font-semibold hover:text-primary">
                                    {p.titulo}
                                </Link>
                                <p className="text-xs text-muted-foreground">
                                    {p.autor.name} · {p.materia.nombre} · {fecha(p.publicado_en)}
                                </p>
                            </div>
                            <Button
                                variant="ghost"
                                size="icon"
                                className="text-destructive"
                                aria-label={`Eliminar ${p.titulo}`}
                                disabled={eliminar.isPending}
                                onClick={() => {
                                    if (window.confirm(`¿Eliminar el proyecto "${p.titulo}"?`)) eliminar.mutate(p.slug);
                                }}
                            >
                                <Trash2 />
                            </Button>
                        </div>
                    ))}
                    <Paginacion meta={lista.data.meta} alCambiar={setPagina} />
                </>
            )}
        </section>
    );
}

function Cuentas({ yo }: { yo: Usuario }) {
    const queryClient = useQueryClient();
    const { texto, setTexto, q } = useBusqueda();
    const [pagina, setPagina] = useState(1);
    useEffect(() => setPagina(1), [q]);

    const lista = useQuery({
        queryKey: ['admin', 'usuarios', pagina, q],
        queryFn: () => admin.usuarios(pagina, q),
        placeholderData: keepPreviousData,
    });

    const eliminar = useMutation({
        mutationFn: (id: number) => admin.eliminarUsuario(id),
        onSuccess: () => {
            toast.success('Cuenta eliminada');
            void queryClient.invalidateQueries({ queryKey: ['admin'] });
            void queryClient.invalidateQueries({ queryKey: ['proyectos'] });
        },
        onError: (e) => toast.error(mensajeDeError(e)),
    });

    return (
        <section>
            <h2 className="mb-4 mt-9 text-xl font-bold">Cuentas ({lista.data?.meta.total ?? '…'})</h2>
            <Buscador valor={texto} alCambiar={setTexto} etiqueta="Buscar por nombre o correo" />
            {lista.isPending ? (
                <Cargando />
            ) : lista.isError ? (
                <ErrorEstado error={lista.error} reintentar={() => void lista.refetch()} />
            ) : !lista.data.data.length ? (
                <Vacio>No hay cuentas{q ? ' con esa búsqueda' : ''}.</Vacio>
            ) : (
                <>
                    {lista.data.data.map((u) => {
                        const soyYo = u.id === yo.id;
                        return (
                            <div key={u.id} className="flex items-center justify-between gap-4 border-b py-4">
                                <div className="min-w-0">
                                    <p className="font-semibold">
                                        {u.name}
                                        {soyYo && <span className="ml-2 text-xs font-normal text-muted-foreground">(tú)</span>}
                                    </p>
                                    <p className="break-all text-xs text-muted-foreground">
                                        {u.email} · {u.role === 'admin' ? 'Administrador' : 'Estudiante'}
                                    </p>
                                </div>
                                <Button
                                    variant="ghost"
                                    size="icon"
                                    className="text-destructive"
                                    disabled={soyYo || eliminar.isPending}
                                    title={soyYo ? 'No puedes eliminar tu propia cuenta' : undefined}
                                    aria-label={`Eliminar cuenta ${u.name}`}
                                    onClick={() => {
                                        if (window.confirm(`¿Eliminar la cuenta de ${u.name} con todas sus publicaciones?`)) eliminar.mutate(u.id);
                                    }}
                                >
                                    <Trash2 />
                                </Button>
                            </div>
                        );
                    })}
                    <Paginacion meta={lista.data.meta} alCambiar={setPagina} />
                </>
            )}
        </section>
    );
}
