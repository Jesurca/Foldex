// Carrusel de fotos de la portada (inicio). Las fotos salen de la carpeta `contexto_img/`:
// basta con agregar o quitar archivos .jpg/.png/.webp ahí y volver a compilar (`npm run build`).

// Imports: React (estado y temporizador), iconos de flechas y la ilustración de respaldo.
import { useEffect, useState, type ReactNode } from 'react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { campus } from '@/lib/formato';

// Vite busca las imágenes al compilar y devuelve sus URLs finales (con hash para caché).
const modulos = import.meta.glob<string>('../../../contexto_img/*.{jpg,jpeg,png,webp,avif,JPG,JPEG,PNG,WEBP}', { eager: true, import: 'default' });

/** Fotos ordenadas por nombre de archivo; si la carpeta está vacía se usa la ilustración del campus. */
export const FOTOS_CONTEXTO: string[] = Object.keys(modulos)
    .sort()
    .map((ruta) => modulos[ruta])
    .filter((url): url is string => Boolean(url));

const INTERVALO_MS = 6000;

export function CarruselHero({ children }: { children: ReactNode }) {
    const fotos = FOTOS_CONTEXTO.length ? FOTOS_CONTEXTO : [campus];
    const [actual, setActual] = useState(0);
    const [pausado, setPausado] = useState(false);
    const varias = fotos.length > 1;

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
