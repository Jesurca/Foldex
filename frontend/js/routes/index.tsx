// Ruta / (inicio): carrusel de fotos, estadísticas y el catálogo de proyectos más valorados con búsqueda y filtros.

// Imports: React Query, router, React, iconos, componentes propios, servicios, tipos y catálogos.
import { keepPreviousData, useQuery } from '@tanstack/react-query';
import { createFileRoute, Link, useNavigate } from '@tanstack/react-router';
import { useEffect, useMemo, useState } from 'react';
import { ArrowRight, LayoutGrid, List, Plus, Search, SlidersHorizontal } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { CarruselHero } from '@/components/CarruselHero';
import { Cargando, ErrorEstado, Paginacion, Vacio } from '@/components/Estados';
import { ProjectCard } from '@/components/ProjectCard';
import { ProjectShell } from '@/components/ProjectShell';
import { proyectos } from '@/lib/api/servicios';
import type { FiltrosProyectos, RangoRanking } from '@/lib/api/tipos';
import { useDocentes, useMaterias, useTecnologias } from '@/lib/consultas';
import { metadata } from '@/lib/formato';

// Filtros que viven en la URL (?q=&materia=&...).
interface BusquedaCatalogo extends Omit<FiltrosProyectos, 'orden'> {
    vista?: 'lista' | undefined;
}

const entero = (v: unknown): number | undefined => {
    const n = Number(v);
    return Number.isInteger(n) && n > 0 ? n : undefined;
};

/** La portada es un ranking por likes; "semanal" es el rango por defecto y no se escribe en la URL. */
const RANGO_POR_DEFECTO: RangoRanking = 'semanal';
const RANGOS: { valor: RangoRanking; etiqueta: string }[] = [
    { valor: 'diario', etiqueta: 'Diario' },
    { valor: 'semanal', etiqueta: 'Semanal' },
    { valor: 'mensual', etiqueta: 'Mensual' },
    { valor: 'anual', etiqueta: 'Anual' },
    { valor: 'historico', etiqueta: 'De todos los tiempos' },
];
const esRango = (v: unknown): v is RangoRanking => RANGOS.some((r) => r.valor === v);

// Registro de la ruta / y validación de los parámetros de la URL.
export const Route = createFileRoute('/')({
    validateSearch: (s: Record<string, unknown>): BusquedaCatalogo => ({
        q: typeof s['q'] === 'string' && s['q'].trim() ? s['q'].slice(0, 100) : undefined,
        materia: entero(s['materia']),
        docente: entero(s['docente']),
        tecnologia: entero(s['tecnologia']),
        semestre: entero(s['semestre']),
        rango: esRango(s['rango']) && s['rango'] !== RANGO_POR_DEFECTO ? s['rango'] : undefined,
        page: entero(s['page']),
        vista: s['vista'] === 'lista' ? 'lista' : undefined,
    }),
    head: () => metadata('Proyectos de Ingeniería de Sistemas', 'Foldex: repositorio de proyectos de Ingeniería de Sistemas de la UNAB.'),
    component: Catalogo,
});

// Página de inicio.
function Catalogo() {
    const busqueda = Route.useSearch();
    const navigate = useNavigate({ from: '/' });
    const { vista, rango: rangoUrl, ...resto } = busqueda;
    const rango = rangoUrl ?? RANGO_POR_DEFECTO;
    const filtros: FiltrosProyectos = { ...resto, rango };
    const lista = vista === 'lista';

    const materias = useMaterias();
    const docentes = useDocentes();
    const tecnologias = useTecnologias();

    const resultado = useQuery({
        queryKey: ['proyectos', 'lista', filtros],
        queryFn: () => proyectos.listar(filtros),
        placeholderData: keepPreviousData,
    });
    const totalGeneral = useQuery({
        queryKey: ['proyectos', 'lista', {}],
        queryFn: () => proyectos.listar({}),
    });

    // `resetScroll: false`: filtrar no debe mandar al usuario al inicio de la página (el router lo hace por defecto).
    const cambiar = (cambios: Partial<BusquedaCatalogo>, reiniciarPagina = true) =>
        void navigate({
            search: (prev) => ({ ...prev, ...cambios, ...(reiniciarPagina ? { page: undefined } : {}) }),
            replace: true,
            resetScroll: false,
        });

    // Búsqueda con debounce de 400 ms: el input es local y la URL se actualiza al dejar de escribir.
    const [texto, setTexto] = useState(busqueda.q ?? '');
    useEffect(() => setTexto(busqueda.q ?? ''), [busqueda.q]);
    useEffect(() => {
        const limpio = texto.trim();
        if (limpio === (busqueda.q ?? '')) return;
        const t = setTimeout(() => cambiar({ q: limpio || undefined }), 400);
        return () => clearTimeout(t);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [texto]);

    const [panel, setPanel] = useState(Boolean(busqueda.materia || busqueda.docente || busqueda.tecnologia || busqueda.semestre));
    const semestres = useMemo(() => [...new Set((materias.data ?? []).map((m) => m.semestre))].sort((a, b) => a - b), [materias.data]);
    const hayFiltros = Boolean(busqueda.q || busqueda.materia || busqueda.docente || busqueda.tecnologia || busqueda.semestre);
    const datos = resultado.data;

    return (
        <ProjectShell>
            <main>
                <section className="site-width pt-8">
                    {/* Carrusel con las fotos de contexto_img/ y el texto de bienvenida encima */}
                    <CarruselHero>
                        <div className="relative z-10 max-w-xl p-7 pb-16 sm:p-10 sm:pb-16 lg:p-12 lg:pb-16">
                            <h1 className="hero-title">Foldex</h1>
                            <p className="mt-4 max-w-md text-xl leading-snug text-balance sm:text-2xl">Los proyectos de Ingeniería de Sistemas de la UNAB, en un solo lugar.</p>
                            <p className="mt-4 max-w-sm text-sm leading-6 opacity-85">
                                Busca por materia, docente o tecnología, abre el repositorio y deja tu comentario.
                            </p>
                            <Button asChild className="mt-7">
                                <Link to="/publicar">
                                    <Plus /> Publicar proyecto
                                </Link>
                            </Button>
                        </div>
                    </CarruselHero>
                    {/* Cifras del catálogo en una sola línea (sin iconos ni ceros de relleno) */}
                    <p className="flex flex-wrap gap-x-6 gap-y-1 border-b py-5 text-sm text-muted-foreground">
                        <Cifra valor={totalGeneral.data?.meta.total} texto="proyectos publicados" />
                        <Cifra valor={materias.data?.length} texto="materias" />
                        <Cifra valor={tecnologias.data?.length} texto="tecnologías en el catálogo" />
                    </p>
                </section>
                <section id="ranking" className="site-width scroll-mt-4 pt-10">
                    <div className="flex flex-wrap items-end justify-between gap-4">
                        <div>
                            <h2 className="text-3xl font-bold">Los más valorados</h2>
                            <p className="mt-2 text-sm text-muted-foreground">Ordenados por los me gusta que recibieron en el periodo que elijas.</p>
                        </div>
                        <Link to="/mi-espacio" className="flex items-center gap-2 text-xs font-semibold hover:text-primary">
                            Ver mi espacio <ArrowRight className="size-4" />
                        </Link>
                    </div>
                    <div className="mt-6 flex flex-wrap gap-3">
                        <div className="relative min-w-48 flex-1">
                            <Search className="absolute left-4 top-3.5 size-4 text-muted-foreground" />
                            <input
                                aria-label="Buscar proyectos"
                                placeholder="Busca por proyecto, tecnología o estudiante..."
                                value={texto}
                                maxLength={100}
                                onChange={(e) => setTexto(e.target.value)}
                                className="field pl-11"
                            />
                        </div>
                        <Button variant="outline" className="h-11" onClick={() => setPanel(!panel)} aria-expanded={panel}>
                            <SlidersHorizontal />
                            Filtros
                        </Button>
                    </div>
                    {panel && (
                        <div className="mt-3 grid gap-3 text-sm sm:grid-cols-2 lg:grid-cols-[1fr_1fr_1fr_1fr_auto] lg:items-end">
                            <label>
                                Semestre
                                <select className="field mt-1" value={busqueda.semestre ?? ''} onChange={(e) => cambiar({ semestre: entero(e.target.value) })}>
                                    <option value="">Todos</option>
                                    {semestres.map((s) => (
                                        <option key={s} value={s}>
                                            Semestre {s}
                                        </option>
                                    ))}
                                </select>
                            </label>
                            <label>
                                Materia
                                <select className="field mt-1" value={busqueda.materia ?? ''} onChange={(e) => cambiar({ materia: entero(e.target.value) })}>
                                    <option value="">Todas</option>
                                    {materias.data?.map((m) => (
                                        <option key={m.id} value={m.id}>
                                            {m.nombre}
                                        </option>
                                    ))}
                                </select>
                            </label>
                            <label>
                                Docente
                                <select className="field mt-1" value={busqueda.docente ?? ''} onChange={(e) => cambiar({ docente: entero(e.target.value) })}>
                                    <option value="">Todos</option>
                                    {docentes.data?.map((d) => (
                                        <option key={d.id} value={d.id}>
                                            {d.nombre}
                                        </option>
                                    ))}
                                </select>
                            </label>
                            <label>
                                Tecnología
                                <select className="field mt-1" value={busqueda.tecnologia ?? ''} onChange={(e) => cambiar({ tecnologia: entero(e.target.value) })}>
                                    <option value="">Todas</option>
                                    {tecnologias.data?.map((t) => (
                                        <option key={t.id} value={t.id}>
                                            {t.nombre}
                                        </option>
                                    ))}
                                </select>
                            </label>
                            <Button
                                variant="ghost"
                                disabled={!hayFiltros}
                                onClick={() => {
                                    setTexto('');
                                    void navigate({ search: (prev) => ({ rango: prev.rango, vista: prev.vista }), replace: true, resetScroll: false });
                                }}
                            >
                                Limpiar
                            </Button>
                        </div>
                    )}
                    <div className="mb-5 mt-5 flex flex-wrap items-center justify-between gap-3 text-xs text-muted-foreground">
                        <div role="group" aria-label="Periodo del ranking" className="flex gap-2 overflow-x-auto pb-1">
                            {RANGOS.map((r) => (
                                <Button
                                    key={r.valor}
                                    variant={rango === r.valor ? 'default' : 'ghost'}
                                    aria-pressed={rango === r.valor}
                                    className="h-9 shrink-0 rounded-full px-4 text-xs"
                                    onClick={() => cambiar({ rango: r.valor === RANGO_POR_DEFECTO ? undefined : r.valor })}
                                >
                                    {r.etiqueta}
                                </Button>
                            ))}
                        </div>
                        <div className="flex items-center gap-3">
                            <span>
                                <strong className="text-foreground">{datos?.meta.total ?? '—'}</strong> proyectos
                            </span>
                            <span className="h-5 border-l" />
                            <Button size="icon" variant={!lista ? 'secondary' : 'ghost'} aria-label="Vista cuadrícula" onClick={() => cambiar({ vista: undefined }, false)}>
                                <LayoutGrid />
                            </Button>
                            <Button size="icon" variant={lista ? 'secondary' : 'ghost'} aria-label="Vista lista" onClick={() => cambiar({ vista: 'lista' }, false)}>
                                <List />
                            </Button>
                        </div>
                    </div>
                    {resultado.isPending ? (
                        <Cargando texto="Cargando proyectos…" />
                    ) : resultado.isError ? (
                        <ErrorEstado error={resultado.error} reintentar={() => void resultado.refetch()} />
                    ) : datos && datos.data.length ? (
                        <>
                            <div className={`${lista ? 'grid gap-5 md:grid-cols-2' : 'grid gap-6 sm:grid-cols-2 lg:grid-cols-3'} ${resultado.isPlaceholderData ? 'opacity-60' : ''}`}>
                                {datos.data.map((p) => (
                                    <ProjectCard key={p.id} project={p} />
                                ))}
                            </div>
                            <Paginacion
                                meta={datos.meta}
                                alCambiar={(page) => {
                                    cambiar({ page: page > 1 ? page : undefined }, false);
                                    document.getElementById('ranking')?.scrollIntoView({ behavior: 'smooth' });
                                }}
                            />
                        </>
                    ) : (
                        <Vacio>{hayFiltros ? 'No encontramos proyectos con esos filtros.' : 'Todavía no hay proyectos publicados.'}</Vacio>
                    )}
                    {/* Cierre: invitación directa a publicar */}
                    <div className="community-band mt-12 flex flex-wrap items-center justify-between gap-6 px-7 py-8">
                        <div>
                            <h2 className="text-2xl font-bold">¿Hiciste un proyecto en alguna materia?</h2>
                            <p className="mt-2 text-sm text-muted-foreground">Publícalo con el repositorio y el docente; así otros estudiantes lo encuentran.</p>
                        </div>
                        <Button asChild variant="outline">
                            <Link to="/publicar">
                                <Plus /> Publicar proyecto
                            </Link>
                        </Button>
                    </div>
                </section>
            </main>
        </ProjectShell>
    );
}

// Una cifra del catálogo: número en negrita (alineación tabular) y su descripción.
function Cifra({ valor, texto }: { valor: number | undefined; texto: string }) {
    return (
        <span>
            <strong className="font-bold text-foreground tabular-nums">{valor ?? '—'}</strong> {texto}
        </span>
    );
}
