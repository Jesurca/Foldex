// Carrusel de fotos de la portada (inicio). Las fotos salen de la carpeta `frontend/contexto_img/`
// y se piden a la API (GET /api/v1/carrusel) en vivo: al copiar una foto nueva en la carpeta,
// aparece sola (al recargar o en máximo 1 minuto) con su propio punto de navegación, sin recompilar.

// Imports: React Query (lista de fotos), React (estado y temporizador), iconos, servicios y la ilustración de respaldo.
import { useQuery } from '@tanstack/react-query';
import { useEffect, useState, type ReactNode } from 'react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { catalogos } from '@/lib/api/servicios';
import { campus } from '@/lib/formato';

const INTERVALO_MS = 6000;
/** Cada cuánto se vuelve a mirar la carpeta mientras la página está abierta. */
const REVISAR_CARPETA_MS = 60_000;

export function CarruselHero({ children }: { children: ReactNode }) {
    // --- Lista de fotos: si la carpeta está vacía o la API falla, se usa la ilustración del campus ---
    const consulta = useQuery({ queryKey: ['carrusel'], queryFn: catalogos.carrusel, refetchInterval: REVISAR_CARPETA_MS, refetchOnWindowFocus: true });
    const urls = consulta.data?.map((f) => f.url) ?? [];
    const fotos = urls.length ? urls : [campus];
    const [elegida, setActual] = useState(0);
    const [pausado, setPausado] = useState(false);
    const varias = fotos.length > 1;
    // Si se borran fotos de la carpeta, el índice se ajusta al nuevo total.
    const actual = elegida % fotos.length;

    // --- Avance automático: se detiene con el mouse encima, con el foco dentro o si el usuario prefiere menos movimiento ---
    useEffect(() => {
        if (!varias || pausado) return;
        if (window.matchMedia?.('(prefers-reduced-motion: reduce)').matches) return;
        const t = setInterval(() => setActual((i) => (i + 1) % fotos.length), INTERVALO_MS);
        return () => clearInterval(t);
    }, [varias, pausado, fotos.length]);

    const ir = (i: number) => setActual((i + fotos.length) % fotos.length);

    return (
        <div
            className="catalog-hero"
            role="region"
            aria-roledescription="carrusel"
            aria-label="Fotos de la comunidad UNAB"
            onMouseEnter={() => setPausado(true)}
            onMouseLeave={() => setPausado(false)}
            onFocus={() => setPausado(true)}
            onBlur={() => setPausado(false)}
        >
            {/* Fotos apiladas: solo la actual es visible (transición de opacidad) */}
            {fotos.map((src, i) => (
                <img
                    key={src}
                    src={src}
                    alt={i === actual ? `Comunidad UNAB, foto ${i + 1} de ${fotos.length}` : ''}
                    aria-hidden={i !== actual}
                    loading={i === 0 ? 'eager' : 'lazy'}
                    // Las fotos verticales se recortan al banner horizontal; 40 % deja visibles las caras.
                    style={{ objectPosition: 'center 40%' }}
                    className={`hero-photo transition-opacity duration-1000 ${i === actual ? 'opacity-100' : 'opacity-0'}`}
                />
            ))}
            <div className="hero-shade" />

            {/* Texto de bienvenida (lo pasa la página de inicio) */}
            {children}

            {/* Controles: flecha anterior, puntos y flecha siguiente (abajo, para no tapar el texto) */}
            {varias && (
                <div className="absolute bottom-4 left-1/2 z-10 flex -translate-x-1/2 items-center gap-3">
                    <button type="button" className="hero-control" aria-label="Foto anterior" onClick={() => ir(actual - 1)}>
                        <ChevronLeft className="size-4" />
                    </button>
                    {fotos.map((src, i) => (
                        <button
                            key={src}
                            type="button"
                            aria-label={`Ver foto ${i + 1}`}
                            aria-current={i === actual}
                            onClick={() => ir(i)}
                            className={`h-2 rounded-full transition-all ${i === actual ? 'w-6 bg-white' : 'w-2 bg-white/50 hover:bg-white/80'}`}
                        />
                    ))}
                    <button type="button" className="hero-control" aria-label="Foto siguiente" onClick={() => ir(actual + 1)}>
                        <ChevronRight className="size-4" />
                    </button>
                </div>
            )}
        </div>
    );
}
