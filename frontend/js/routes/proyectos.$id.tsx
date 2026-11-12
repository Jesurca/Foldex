// Ruta /proyectos/$id: detalle de un proyecto (video o portada, contexto, autor, me gusta, comentarios) y acceso a "Conéctate por MCP".

// Imports: React Query, router, React, iconos, avisos, componentes propios, servicios y utilidades.
import { useInfiniteQuery, useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { createFileRoute, Link, useNavigate } from '@tanstack/react-router';
import { useState } from 'react';
import { ArrowLeft, BookOpen, Calendar, CalendarRange, Github, GraduationCap, Heart, Loader2, Pencil, Play, Plug, Send, Trash2 } from 'lucide-react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import { BotonConectarMcp } from '@/components/ConectarMcp';
import { Cargando, ErrorEstado } from '@/components/Estados';
import { ProjectShell } from '@/components/ProjectShell';
import { ApiError, mensajeDeError } from '@/lib/api/client';
import { comentarios, proyectos } from '@/lib/api/servicios';
import type { Proyecto } from '@/lib/api/tipos';
import { useAuth } from '@/lib/auth';
import { useLike } from '@/lib/consultas';
import { fecha, iniciales, metadata, portada, urlVideoEmbebido } from '@/lib/formato';

// Registro de la ruta; $id es el slug del proyecto.
export const Route = createFileRoute('/proyectos/$id')({
    head: () => metadata('Detalle del proyecto', 'Conoce el contexto, las tecnologías y los creadores de un proyecto universitario.'),
    component: Detalle,
});

// Carga el proyecto y maneja 404 y errores.
function Detalle() {
    const { id: slug } = Route.useParams();
    const consulta = useQuery({ queryKey: ['proyectos', 'detalle', slug], queryFn: () => proyectos.detalle(slug) });

    if (consulta.isPending) {
        return (
            <ProjectShell>
                <Cargando texto="Cargando proyecto…" />
            </ProjectShell>
        );
    }

    if (consulta.isError) {
        const noExiste = consulta.error instanceof ApiError && consulta.error.status === 404;
        return (
            <ProjectShell>
                <main className="site-width py-20">
                    {noExiste ? (
                        <>
                            <h1 className="text-2xl font-bold">Proyecto no encontrado</h1>
                            <p className="mt-2 text-muted-foreground">Puede que haya sido eliminado.</p>
                            <Link to="/" className="mt-6 inline-flex items-center gap-2 text-sm font-semibold text-primary">
                                <ArrowLeft className="size-4" />
                                Volver a la biblioteca
                            </Link>
                        </>
                    ) : (
                        <ErrorEstado error={consulta.error} reintentar={() => void consulta.refetch()} />
                    )}
                </main>
            </ProjectShell>
        );
    }

    return <Ficha p={consulta.data} />;
}

// Contenido del proyecto. Editar aparece si can.update (admin) y Eliminar si can.delete (dueño o admin).
function Ficha({ p }: { p: Proyecto }) {
    const alternarLike = useLike();
    const navigate = useNavigate();
    const queryClient = useQueryClient();
    const video = urlVideoEmbebido(p.video_url);

    const eliminar = useMutation({
        mutationFn: () => proyectos.eliminar(p.slug),
        onSuccess: () => {
            toast.success('Proyecto eliminado');
            queryClient.removeQueries({ queryKey: ['proyectos', 'detalle', p.slug] });
            void queryClient.invalidateQueries({ queryKey: ['proyectos'] });
            void navigate({ to: '/' });
        },
        onError: (e) => toast.error(mensajeDeError(e)),
    });

    return (
        <ProjectShell>
            <main className="site-width py-8">
                <Link to="/" className="mb-6 inline-flex items-center gap-2 text-sm text-muted-foreground">
                    <ArrowLeft className="size-4" />
                    Volver a los proyectos
                </Link>
                <div className="grid gap-10 lg:grid-cols-[1fr_320px]">
                    <div className="min-w-0">
                        {video ? (
                            <div className="aspect-video w-full overflow-hidden rounded-lg bg-black">
                                <iframe
                                    src={video}
                                    title={`Video de ${p.titulo}`}
                                    className="size-full"
                                    allow="accelerometer; encrypted-media; gyroscope; picture-in-picture"
                                    allowFullScreen
                                    loading="lazy"
                                    referrerPolicy="strict-origin-when-cross-origin"
                                />
                            </div>
                        ) : (
                            <img src={portada(p)} alt={p.titulo} width={1024} height={768} className="max-h-96 w-full rounded-lg object-cover" />
                        )}
                        <p className="section-eyebrow mt-7">
                            {p.materia.nombre.toUpperCase()} · SEMESTRE {p.materia.semestre}
                        </p>
                        <h1 className="mt-3 text-4xl font-bold [overflow-wrap:anywhere]">{p.titulo}</h1>
                        {/* overflow-wrap:anywhere: enlaces o palabras muy largas se parten en vez de salirse por la derecha */}
                        <p className="mt-5 whitespace-pre-line text-base leading-7 text-muted-foreground [overflow-wrap:anywhere]">{p.contexto}</p>
                        <h2 className="mt-8 text-xl font-bold">Tecnologías utilizadas</h2>
                        <div className="mt-3 flex flex-wrap gap-2">
                            {p.tecnologias.map((t) => (
                                <span className="tech-tag" key={t.id}>
                                    {t.nombre}
                                </span>
                            ))}
                        </div>
                        <div className="mt-7 flex flex-wrap gap-3">
                            <Button asChild variant="outline">
                                <a href={p.repositorio_url} target="_blank" rel="noopener noreferrer">
                                    <Github />
                                    Ver repositorio
                                </a>
                            </Button>
                            {p.video_url && !video && (
                                <Button asChild variant="outline">
                                    <a href={p.video_url} target="_blank" rel="noopener noreferrer">
                                        <Play />
                                        Ver aplicación en funcionamiento
                                    </a>
                                </Button>
                            )}
                            <Button variant={p.liked ? 'default' : 'outline'} aria-pressed={p.liked ?? false} onClick={() => alternarLike(p)}>
                                <Heart className={p.liked ? 'fill-current' : ''} />
                                {p.likes_count} Me gusta
                            </Button>
                            {p.can.update && (
                                <Button asChild variant="ghost">
                                    <Link to="/editar/$id" params={{ id: p.slug }}>
                                        <Pencil />
                                        Editar proyecto
                                    </Link>
                                </Button>
                            )}
                            {p.can.delete && (
                                <Button
                                    variant="ghost"
                                    className="text-destructive"
                                    disabled={eliminar.isPending}
                                    onClick={() => {
                                        if (window.confirm(`¿Eliminar el proyecto "${p.titulo}"? Esta acción no se puede deshacer desde la aplicación.`)) eliminar.mutate();
                                    }}
                                >
                                    <Trash2 />
                                    Eliminar proyecto
                                </Button>
                            )}
                        </div>
                        <Conversacion p={p} />
                    </div>
                    <aside className="space-y-7">
                        <section className="border-b pb-6">
                            <h2 className="text-sm font-bold">DETRÁS DEL PROYECTO</h2>
                            <div className="mt-5 flex items-center gap-3">
                                <span className="avatar-mini size-11">{iniciales(p.autor.name)}</span>
                                <div>
                                    <p className="font-bold">{p.autor.name}</p>
                                    <p className="text-xs text-muted-foreground">
                                        {p.autor.role === 'admin' ? 'Administrador' : 'Estudiante'} · {p.autor.programa ?? 'Ingeniería de Sistemas'}
                                    </p>
                                </div>
                            </div>
                            <div className="mt-6 space-y-5 text-sm">
                                <p className="flex gap-3">
                                    <GraduationCap className="size-4 shrink-0 text-primary" />
                                    <span>
                                        <span className="block text-xs text-muted-foreground">Docente director</span>
                                        {p.docente.nombre}
                                    </span>
                                </p>
                                <p className="flex gap-3">
                                    <BookOpen className="size-4 shrink-0 text-primary" />
                                    <span>
                                        <span className="block text-xs text-muted-foreground">Materia</span>
                                        {p.materia.nombre} ({p.materia.codigo}) · Semestre {p.materia.semestre}
                                    </span>
                                </p>
                                <p className="flex gap-3">
                                    <CalendarRange className="size-4 shrink-0 text-primary" />
                                    <span>
                                        <span className="block text-xs text-muted-foreground">Periodo académico</span>
                                        {p.periodo}
                                    </span>
                                </p>
                                <p className="flex gap-3">
                                    <Calendar className="size-4 shrink-0 text-primary" />
                                    <span>
                                        <span className="block text-xs text-muted-foreground">Publicado</span>
                                        {fecha(p.publicado_en)}
                                    </span>
                                </p>
                            </div>
                        </section>
                        <ConectaIA />
                    </aside>
                </div>
            </main>
        </ProjectShell>
    );
}

// Comentarios paginados: listar, publicar y borrar.
function Conversacion({ p }: { p: Proyecto }) {
    const { usuario, abrirModal } = useAuth();
    const queryClient = useQueryClient();
    const [texto, setTexto] = useState('');
    const [error, setError] = useState<string | null>(null);
    const clave = ['comentarios', p.slug];

    const lista = useInfiniteQuery({
        queryKey: [...clave, usuario?.id ?? null],
        queryFn: ({ pageParam }) => comentarios.listar(p.slug, pageParam),
        initialPageParam: 1,
        getNextPageParam: (ultima) => (ultima.meta.current_page < ultima.meta.last_page ? ultima.meta.current_page + 1 : undefined),
    });

    const refrescar = () => {
        void queryClient.invalidateQueries({ queryKey: clave });
        void queryClient.invalidateQueries({ queryKey: ['proyectos'] });
    };

    const crear = useMutation({
        mutationFn: (contenido: string) => comentarios.crear(p.slug, contenido),
        onSuccess: () => {
            setTexto('');
            setError(null);
            toast.success('Comentario publicado');
            refrescar();
        },
        onError: (e) => setError(e instanceof ApiError ? (e.campo('contenido') ?? e.message) : mensajeDeError(e)),
    });

    const borrar = useMutation({
        mutationFn: (id: number) => comentarios.eliminar(id),
        onSuccess: () => {
            toast.success('Comentario eliminado');
            refrescar();
        },
        onError: (e) => toast.error(mensajeDeError(e)),
    });

    const items = lista.data?.pages.flatMap((pg) => pg.data) ?? [];
    const total = lista.data?.pages[0]?.meta.total ?? p.comentarios_count;

    return (
        <section id="conversacion" className="mt-10 scroll-mt-6 border-t pt-7">
            <h2 className="text-xl font-bold">
                Conversación <span className="text-muted-foreground">({total})</span>
            </h2>
            {usuario ? (
                <form
                    className="mt-5"
                    onSubmit={(e) => {
                        e.preventDefault();
                        if (texto.trim().length < 2) {
                            setError('Escribe al menos 2 caracteres.');
                            return;
                        }
                        crear.mutate(texto);
                    }}
                >
                    <label htmlFor="comment" className="text-sm font-semibold">
                        Aporta a la conversación
                    </label>
                    <textarea
                        id="comment"
                        value={texto}
                        maxLength={1000}
                        onChange={(e) => setTexto(e.target.value)}
                        className="field mt-2 min-h-28 py-3"
                        aria-invalid={Boolean(error)}
                        placeholder="Comparte una pregunta o una idea..."
                    />
                    <div className="mt-1 flex justify-between text-xs">
                        <span className="text-destructive">{error}</span>
                        <span className="text-muted-foreground">{texto.length}/1000</span>
                    </div>
                    <Button type="submit" className="mt-3" disabled={crear.isPending}>
                        {crear.isPending ? <Loader2 className="animate-spin" /> : <Send />}
                        Publicar comentario
                    </Button>
                </form>
            ) : (
                <p className="mt-5 text-sm text-muted-foreground">
                    <button className="font-semibold text-primary underline-offset-4 hover:underline" onClick={() => abrirModal('entrar')}>
                        Ingresa
                    </button>{' '}
                    para comentar este proyecto.
                </p>
            )}
            {lista.isPending ? (
                <Cargando texto="Cargando comentarios…" />
            ) : lista.isError ? (
                <ErrorEstado error={lista.error} reintentar={() => void lista.refetch()} />
            ) : (
                <>
                    {!items.length && <p className="mt-5 text-sm text-muted-foreground">Aún no hay comentarios. ¡Inicia la conversación!</p>}
                    {items.map((c) => (
                        <div key={c.id} className="mt-5 border-b pb-5">
                            <div className="flex items-start justify-between gap-3">
                                <p className="text-sm font-bold">
                                    {c.autor.name}
                                    {c.autor.role === 'admin' && <span className="ml-2 text-xs font-normal text-muted-foreground">· Administrador</span>}
                                </p>
                                <div className="flex items-center gap-1">
                                    <time className="text-xs text-muted-foreground">{fecha(c.creado_en)}</time>
                                    {c.can.delete && (
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            className="size-7 text-destructive"
                                            aria-label="Eliminar comentario"
                                            disabled={borrar.isPending}
                                            onClick={() => {
                                                if (window.confirm('¿Eliminar este comentario?')) borrar.mutate(c.id);
                                            }}
                                        >
                                            <Trash2 />
                                        </Button>
                                    )}
                                </div>
                            </div>
                            <p className="mt-2 whitespace-pre-line break-words text-sm leading-6 text-muted-foreground">{c.contenido}</p>
                        </div>
                    ))}
                    {lista.hasNextPage && (
                        <Button variant="outline" className="mt-5" disabled={lista.isFetchingNextPage} onClick={() => void lista.fetchNextPage()}>
                            {lista.isFetchingNextPage && <Loader2 className="animate-spin" />}
                            Ver más comentarios
                        </Button>
                    )}
                </>
            )}
        </section>
    );
}

/** Invita a publicar desde Claude: el botón abre los pasos para conectar el servidor MCP (/mcp/proyectos). */
function ConectaIA() {
    return (
        <section>
            <Plug className="size-6 text-primary" />
            <h2 className="mt-3 text-lg font-bold">Publica desde tu IA</h2>
            <p className="mt-2 text-sm leading-6 text-muted-foreground">
                Conecta Claude a tu cuenta por MCP y publica tu proyecto con un mensaje: tú das el repositorio y el docente; Claude escribe la descripción y elige la foto.
            </p>
            <BotonConectarMcp variante="outline" className="mt-4 w-full" />
        </section>
    );
}
