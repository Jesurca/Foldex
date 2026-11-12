import { AlertTriangle, ChevronLeft, ChevronRight, Loader2, RotateCw } from 'lucide-react';
import type { ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import { mensajeDeError } from '@/lib/api/client';
import type { MetaPaginacion } from '@/lib/api/tipos';

export function Cargando({ texto = 'Cargando…' }: { texto?: string }) {
    return (
        <div className="flex items-center justify-center gap-2 py-16 text-sm text-muted-foreground" role="status">
            <Loader2 className="size-4 animate-spin" />
            {texto}
        </div>
    );
}

export function ErrorEstado({ error, reintentar }: { error: unknown; reintentar?: () => void }) {
    return (
        <div className="flex flex-col items-center gap-3 py-16 text-center" role="alert">
            <AlertTriangle className="size-8 text-destructive" />
            <p className="text-sm text-muted-foreground">{mensajeDeError(error)}</p>
            {reintentar && (
                <Button variant="outline" onClick={reintentar}>
                    <RotateCw />
                    Reintentar
                </Button>
            )}
        </div>
    );
}

export function Vacio({ children }: { children: ReactNode }) {
    return <p className="py-16 text-center text-muted-foreground">{children}</p>;
}

export function Paginacion({ meta, alCambiar }: { meta: MetaPaginacion; alCambiar: (pagina: number) => void }) {
    if (meta.last_page <= 1) return null;
    return (
        <nav className="mt-8 flex items-center justify-center gap-3 text-sm" aria-label="Paginación">
            <Button variant="outline" size="sm" disabled={meta.current_page <= 1} onClick={() => alCambiar(meta.current_page - 1)}>
                <ChevronLeft />
                Anterior
            </Button>
            <span className="text-muted-foreground">
                Página <strong className="text-foreground">{meta.current_page}</strong> de {meta.last_page}
            </span>
            <Button variant="outline" size="sm" disabled={meta.current_page >= meta.last_page} onClick={() => alCambiar(meta.current_page + 1)}>
                Siguiente
                <ChevronRight />
            </Button>
        </nav>
    );
}
