import { useMutation, useQueryClient } from '@tanstack/react-query';
import { createFileRoute, Link, useNavigate } from '@tanstack/react-router';
import { useEffect, useMemo, useRef, useState, type ChangeEvent, type FormEvent } from 'react';
import { ArrowLeft, CalendarDays, ImagePlus, Loader2, Upload, X } from 'lucide-react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import { Cargando, ErrorEstado } from '@/components/Estados';
import { EmptyAccount, ProjectShell } from '@/components/ProjectShell';
import { ApiError, mensajeDeError } from '@/lib/api/client';
import { proyectos } from '@/lib/api/servicios';
import type { NuevoProyecto } from '@/lib/api/tipos';
import { useAuth } from '@/lib/auth';
import { useDocentes, useMaterias, useTecnologias } from '@/lib/consultas';
import { errorPortada, metadata, PORTADA_TIPOS } from '@/lib/formato';

export const Route = createFileRoute('/publicar')({
    head: () => metadata('Publica tu proyecto', 'Comparte un proyecto académico con la comunidad de ingeniería.'),
    component: Publicar,
});

/** Periodos académicos desde el actual hacia atrás (formato AAAA-1 / AAAA-2, como exige el backend). */
function periodos(): string[] {
    const hoy = new Date();
    const anio = hoy.getFullYear();
    const lista: string[] = [];
    for (let a = anio; a >= anio - 5; a--) {
        if (a < anio || hoy.getMonth() >= 6) lista.push(`${a}-2`);
        lista.push(`${a}-1`);
    }
    return lista;
}

type Errores = Record<string, string | undefined>;

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
    return <Formulario nombre={usuario.name} email={usuario.email} />;
}

function Formulario({ nombre, email }: { nombre: string; email: string }) {
    const navigate = useNavigate();
    const queryClient = useQueryClient();
    const materias = useMaterias();
    const docentes = useDocentes();
    const tecnologias = useTecnologias();
    const opcionesPeriodo = useMemo(periodos, []);
    const [elegidas, setElegidas] = useState<number[]>([]);
    const [errores, setErrores] = useState<Errores>({});
    const [portada, setPortada] = useState<File | null>(null);
    const inputPortada = useRef<HTMLInputElement>(null);

    // Vista previa local con un object URL; el mismo efecto que lo crea lo libera
    // (así también funciona con el doble montaje de StrictMode).
    const [vistaPrevia, setVistaPrevia] = useState<string | null>(null);
    useEffect(() => {
        if (!portada) {
            setVistaPrevia(null);
            return;
        }
        const url = URL.createObjectURL(portada);
        setVistaPrevia(url);
        return () => URL.revokeObjectURL(url);
    }, [portada]);

    const elegirPortada = (e: ChangeEvent<HTMLInputElement>) => {
        const archivo = e.target.files?.[0];
        e.target.value = ''; // permite volver a elegir el mismo archivo
        if (!archivo) return;
        const problema = errorPortada(archivo);
        setErrores((prev) => ({ ...prev, portada: problema }));
        if (!problema) setPortada(archivo);
    };
    const quitarPortada = () => {
        setPortada(null);
        setErrores((prev) => ({ ...prev, portada: undefined }));
    };

    const publicar = useMutation({
        mutationFn: (datos: NuevoProyecto) => proyectos.publicar(datos),
        onSuccess: (p) => {
            toast.success('¡Proyecto publicado!');
            void queryClient.invalidateQueries({ queryKey: ['proyectos'] });
            queryClient.setQueryData(['proyectos', 'detalle', p.slug], p);
            void navigate({ to: '/proyectos/$id', params: { id: p.slug } });
        },
        onError: (e) => {
            if (e instanceof ApiError && e.status === 422) {
                const porCampo: Errores = {};
                for (const [campo, mensajes] of Object.entries(e.errors)) {
                    porCampo[campo.startsWith('tecnologias') ? 'tecnologias' : campo] ??= mensajes[0];
                }
                setErrores(porCampo);
                toast.error('Revisa los campos marcados.');
            } else {
                toast.error(mensajeDeError(e));
            }
        },
    });

    const enviar = (e: FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        const f = new FormData(e.currentTarget);
        const valor = (k: string) => String(f.get(k) ?? '').trim();
        if (!elegidas.length) {
            setErrores({ tecnologias: 'Selecciona al menos una tecnología.' });
            return;
        }
        setErrores({});
        publicar.mutate({
            titulo: valor('titulo'),
            contexto: valor('contexto'),
            materia_id: Number(valor('materia_id')),
            docente_id: Number(valor('docente_id')),
            periodo: valor('periodo'),
            repositorio_url: valor('repositorio_url'),
            video_url: valor('video_url') || null,
            tecnologias: elegidas,
            portada,
        });
    };

    const alternar = (id: number) => setElegidas((l) => (l.includes(id) ? l.filter((x) => x !== id) : l.length >= 10 ? l : [...l, id]));

    const campo = (n: string) => ({
        name: n,
        'aria-invalid': Boolean(errores[n]),
        'aria-describedby': errores[n] ? `error-${n}` : undefined,
    });
    const err = (n: string) =>
        errores[n] ? (
            <span id={`error-${n}`} className="mt-1 block text-xs font-normal text-destructive">
                {errores[n]}
            </span>
        ) : null;

    const catalogoError = materias.error ?? docentes.error ?? tecnologias.error;

    return (
        <ProjectShell>
            <main className="site-width max-w-3xl py-10">
                <Link to="/" className="flex items-center gap-2 text-sm text-muted-foreground">
                    <ArrowLeft className="size-4" />
                    Volver a la biblioteca
                </Link>
                <p className="section-eyebrow mt-8">COMPARTE LO QUE ESTÁS CREANDO</p>
                <h1 className="mt-2 text-3xl font-bold">Publicar proyecto</h1>
                <p className="mt-3 text-sm text-muted-foreground">
                    {nombre} · {email}
                </p>
                {catalogoError ? (
                    <ErrorEstado
                        error={catalogoError}
                        reintentar={() => {
                            void materias.refetch();
                            void docentes.refetch();
                            void tecnologias.refetch();
                        }}
                    />
                ) : (
                    <form className="mt-8 grid gap-5" onSubmit={enviar} noValidate>
                        <div>
                            <p className="form-label text-sm">
                                Foto de portada <span className="font-normal text-muted-foreground">(opcional · JPG, PNG o WebP, máx. 2 MB)</span>
                            </p>
                            <input
                                ref={inputPortada}
                                id="portada"
                                type="file"
                                accept={PORTADA_TIPOS.join(',')}
                                className="sr-only"
                                aria-invalid={Boolean(errores['portada'])}
                                aria-describedby={errores['portada'] ? 'error-portada' : undefined}
                                onChange={elegirPortada}
                            />
                            {vistaPrevia ? (
                                <div className="relative mt-2 overflow-hidden rounded-lg border">
                                    <img src={vistaPrevia} alt="Vista previa de la portada" className="aspect-[1.85] w-full object-cover" />
                                    <div className="absolute right-3 top-3 flex gap-2">
                                        <Button type="button" size="sm" variant="secondary" onClick={() => inputPortada.current?.click()}>
                                            <ImagePlus />
                                            Cambiar
                                        </Button>
                                        <Button type="button" size="sm" variant="secondary" aria-label="Quitar foto de portada" onClick={quitarPortada}>
                                            <X />
                                        </Button>
                                    </div>
                                </div>
                            ) : (
                                <label
                                    htmlFor="portada"
                                    className="mt-2 flex aspect-[1.85] cursor-pointer flex-col items-center justify-center gap-2 rounded-lg border border-dashed text-sm text-muted-foreground transition-colors hover:border-primary hover:text-foreground"
                                >
                                    <ImagePlus className="size-7" />
                                    <span className="font-semibold">Sube una foto de portada</span>
                                    <span className="text-xs">Si no subes una, se usará una ilustración.</span>
                                </label>
                            )}
                            {err('portada')}
                        </div>
                        <label className="form-label">
                            Nombre del proyecto
                            <input {...campo('titulo')} required minLength={3} maxLength={150} className="field mt-2" placeholder="Dale un nombre a tu idea" />
                            {err('titulo')}
                        </label>
                        <label className="form-label">
                            Contexto y descripción
                            <textarea
                                {...campo('contexto')}
                                required
                                minLength={20}
                                maxLength={5000}
                                className="field mt-2 min-h-32 py-3"
                                placeholder="¿Qué problema resuelve y cómo funciona? (mínimo 20 caracteres)"
                            />
                            {err('contexto')}
                        </label>
                        <div className="grid gap-5 sm:grid-cols-2">
                            <label className="form-label">
                                Materia
                                <select {...campo('materia_id')} required className="field mt-2" defaultValue="" disabled={materias.isPending}>
                                    <option value="" disabled>
                                        {materias.isPending ? 'Cargando…' : 'Selecciona la materia'}
                                    </option>
                                    {materias.data?.map((m) => (
                                        <option key={m.id} value={m.id}>
                                            {m.semestre}° · {m.nombre}
                                        </option>
                                    ))}
                                </select>
                                {err('materia_id')}
                            </label>
                            <label className="form-label">
                                Periodo académico
                                <select {...campo('periodo')} required className="field mt-2" defaultValue={opcionesPeriodo[0]}>
                                    {opcionesPeriodo.map((p) => (
                                        <option key={p}>{p}</option>
                                    ))}
                                </select>
                                {err('periodo')}
                            </label>
                        </div>
                        <label className="form-label">
                            Docente que dirigió el proyecto
                            <select {...campo('docente_id')} required className="field mt-2" defaultValue="" disabled={docentes.isPending}>
                                <option value="" disabled>
                                    {docentes.isPending ? 'Cargando…' : 'Selecciona el docente'}
                                </option>
                                {docentes.data?.map((d) => (
                                    <option key={d.id} value={d.id}>
                                        {d.nombre}
                                    </option>
                                ))}
                            </select>
                            {err('docente_id')}
                        </label>
                        <fieldset aria-describedby={errores['tecnologias'] ? 'error-tecnologias' : undefined}>
                            <legend className="form-label text-sm">
                                Tecnologías utilizadas <span className="font-normal text-muted-foreground">({elegidas.length}/10)</span>
                            </legend>
                            <div className="mt-2 flex flex-wrap gap-2">
                                {tecnologias.isPending && <span className="text-sm text-muted-foreground">Cargando…</span>}
                                {tecnologias.data?.map((t) => {
                                    const activa = elegidas.includes(t.id);
                                    return (
                                        <Button
                                            key={t.id}
                                            type="button"
                                            size="sm"
                                            variant={activa ? 'default' : 'outline'}
                                            className="rounded-full"
                                            aria-pressed={activa}
                                            onClick={() => alternar(t.id)}
                                        >
                                            {t.nombre}
                                        </Button>
                                    );
                                })}
                            </div>
                            {err('tecnologias')}
                        </fieldset>
                        <label className="form-label">
                            Enlace del repositorio
                            <input {...campo('repositorio_url')} type="url" required maxLength={500} className="field mt-2" placeholder="https://github.com/..." />
                            {err('repositorio_url')}
                        </label>
                        <label className="form-label">
                            Video de la aplicación en funcionamiento <span className="font-normal text-muted-foreground">(opcional)</span>
                            <input {...campo('video_url')} type="url" maxLength={500} className="field mt-2" placeholder="https://youtube.com/... o https://drive.google.com/..." />
                            {err('video_url')}
                        </label>
                        <p className="flex items-center gap-2 text-xs text-muted-foreground">
                            <CalendarDays className="size-4" />
                            El autor y la fecha de publicación los asigna el sistema automáticamente.
                        </p>
                        <Button type="submit" size="lg" className="justify-self-start" disabled={publicar.isPending}>
                            {publicar.isPending ? <Loader2 className="animate-spin" /> : <Upload />}
                            Publicar proyecto
                        </Button>
                    </form>
                )}
            </main>
        </ProjectShell>
    );
}
