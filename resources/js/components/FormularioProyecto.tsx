// Formulario de proyecto compartido por "Publicar" (estudiante) y "Editar" (solo admin).
// Los campos y las reglas son los de StoreProyectoRequest en el backend.

// Imports: React Query (envío), React (estado del formulario), iconos, avisos (toast)
// y piezas propias: botón, estados de carga/error, cliente API y catálogos cacheados.
import { useMutation } from '@tanstack/react-query';
import { useEffect, useMemo, useRef, useState, type ChangeEvent, type FormEvent } from 'react';
import { CalendarDays, ImagePlus, Loader2, Upload, X } from 'lucide-react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import { ErrorEstado } from '@/components/Estados';
import { ApiError, mensajeDeError } from '@/lib/api/client';
import type { NuevoProyecto, Proyecto } from '@/lib/api/tipos';
import { useDocentes, useMaterias, useTecnologias } from '@/lib/consultas';
import { errorPortada, PORTADA_TIPOS } from '@/lib/formato';

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

interface Props {
    /** Proyecto a editar. Sin él, el formulario arranca vacío (publicar). */
    inicial?: Proyecto;
    textoBoton: string;
    /** Llama a la API: `proyectos.publicar` o `proyectos.actualizar`. */
    guardar: (datos: NuevoProyecto) => Promise<Proyecto>;
    alGuardar: (p: Proyecto) => void;
}

export function FormularioProyecto({ inicial, textoBoton, guardar, alGuardar }: Props) {
    // --- Catálogos (materias, docentes, tecnologías) cacheados con React Query ---
    const materias = useMaterias();
    const docentes = useDocentes();
    const tecnologias = useTecnologias();
    const opcionesPeriodo = useMemo(() => {
        const lista = periodos();
        // Un proyecto viejo puede tener un periodo fuera de los últimos 5 años: se conserva.
        return inicial && !lista.includes(inicial.periodo) ? [...lista, inicial.periodo] : lista;
    }, [inicial]);

    // --- Estado local: tecnologías elegidas, errores por campo y foto de portada ---
    const [elegidas, setElegidas] = useState<number[]>(() => inicial?.tecnologias.map((t) => t.id) ?? []);
    const [errores, setErrores] = useState<Errores>({});
    const [portada, setPortada] = useState<File | null>(null);
    const [quitarActual, setQuitarActual] = useState(false);
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
    // Al editar, si no hay foto nueva se muestra la actual (salvo que el admin la quite).
    const imagenMostrada = vistaPrevia ?? (!quitarActual ? (inicial?.portada_url ?? null) : null);

    const elegirPortada = (e: ChangeEvent<HTMLInputElement>) => {
        const archivo = e.target.files?.[0];
        e.target.value = ''; // permite volver a elegir el mismo archivo
        if (!archivo) return;
        const problema = errorPortada(archivo);
        setErrores((prev) => ({ ...prev, portada: problema }));
        if (!problema) setPortada(archivo);
    };
    const quitarPortada = () => {
        if (portada) setPortada(null);
        else setQuitarActual(true);
        setErrores((prev) => ({ ...prev, portada: undefined }));
    };

    // --- Envío: los errores 422 de Laravel se muestran debajo de cada campo ---
    const envio = useMutation({
        mutationFn: guardar,
        onSuccess: alGuardar,
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
        envio.mutate({
            titulo: valor('titulo'),
            contexto: valor('contexto'),
            materia_id: Number(valor('materia_id')),
            docente_id: Number(valor('docente_id')),
            periodo: valor('periodo'),
            repositorio_url: valor('repositorio_url'),
            video_url: valor('video_url') || null,
            tecnologias: elegidas,
            portada,
            quitar_portada: quitarActual && !portada,
        });
    };

    const alternar = (id: number) => setElegidas((l) => (l.includes(id) ? l.filter((x) => x !== id) : l.length >= 10 ? l : [...l, id]));

    // --- Ayudas de accesibilidad: cada campo apunta a su mensaje de error ---
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
    if (catalogoError) {
        return (
            <ErrorEstado
                error={catalogoError}
                reintentar={() => {
                    void materias.refetch();
                    void docentes.refetch();
                    void tecnologias.refetch();
                }}
            />
        );
    }

    // --- Campos del formulario ---
    return (
        <form className="mt-8 grid gap-5" onSubmit={enviar} noValidate>
            {/* Foto de portada (opcional) */}
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
                {imagenMostrada ? (
                    <div className="relative mt-2 overflow-hidden rounded-lg border">
                        <img src={imagenMostrada} alt="Vista previa de la portada" className="aspect-[1.85] w-full object-cover" />
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

            {/* Título y descripción */}
            <label className="form-label">
                Nombre del proyecto
                <input {...campo('titulo')} defaultValue={inicial?.titulo} required minLength={3} maxLength={150} className="field mt-2" placeholder="Dale un nombre a tu idea" />
                {err('titulo')}
            </label>
            <label className="form-label">
                Contexto y descripción
                <textarea
                    {...campo('contexto')}
                    defaultValue={inicial?.contexto}
                    required
                    minLength={20}
                    maxLength={5000}
                    className="field mt-2 min-h-32 py-3"
                    placeholder="¿Qué problema resuelve y cómo funciona? (mínimo 20 caracteres)"
                />
                {err('contexto')}
            </label>

            {/* Materia y periodo */}
            <div className="grid gap-5 sm:grid-cols-2">
                <label className="form-label">
                    Materia
                    <select
                        {...campo('materia_id')}
                        // `key`: cuando llega el catálogo, el select se vuelve a montar con el valor inicial.
                        key={materias.data ? 'listo' : 'cargando'}
                        required
                        className="field mt-2"
                        defaultValue={inicial?.materia.id ?? ''}
                        disabled={materias.isPending}
                    >
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
                    <select {...campo('periodo')} required className="field mt-2" defaultValue={inicial?.periodo ?? opcionesPeriodo[0]}>
                        {opcionesPeriodo.map((p) => (
                            <option key={p}>{p}</option>
                        ))}
                    </select>
                    {err('periodo')}
                </label>
            </div>

            {/* Docente */}
            <label className="form-label">
                Docente que dirigió el proyecto
                <select
                    {...campo('docente_id')}
                    key={docentes.data ? 'listo' : 'cargando'}
                    required
                    className="field mt-2"
                    defaultValue={inicial?.docente.id ?? ''}
                    disabled={docentes.isPending}
                >
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

            {/* Tecnologías (de 1 a 10) */}
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

            {/* Enlaces */}
            <label className="form-label">
                Enlace del repositorio
                <input {...campo('repositorio_url')} defaultValue={inicial?.repositorio_url} type="url" required maxLength={500} className="field mt-2" placeholder="https://github.com/..." />
                {err('repositorio_url')}
            </label>
            <label className="form-label">
                Video de la aplicación en funcionamiento <span className="font-normal text-muted-foreground">(opcional)</span>
                <input
                    {...campo('video_url')}
                    defaultValue={inicial?.video_url ?? ''}
                    type="url"
                    maxLength={500}
                    className="field mt-2"
                    placeholder="https://youtube.com/... o https://drive.google.com/..."
                />
                {err('video_url')}
            </label>

            <p className="flex items-center gap-2 text-xs text-muted-foreground">
                <CalendarDays className="size-4" />
                {inicial ? 'El autor y la fecha de publicación no cambian al editar.' : 'El autor y la fecha de publicación los asigna el sistema automáticamente.'}
            </p>
            <Button type="submit" size="lg" className="justify-self-start" disabled={envio.isPending}>
                {envio.isPending ? <Loader2 className="animate-spin" /> : <Upload />}
                {textoBoton}
            </Button>
        </form>
    );
}
