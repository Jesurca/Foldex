// Pruebas del carrusel de la portada: pide las fotos a la API y crea un punto por cada foto.
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { fireEvent, render, screen } from '@testing-library/react';
import { afterEach, describe, expect, it, vi } from 'vitest';
import { CarruselHero } from '@/components/CarruselHero';

/** Simula GET /api/v1/carrusel con la cantidad de fotos indicada. */
function simularFotos(cantidad: number) {
    const data = Array.from({ length: cantidad }, (_, i) => ({ nombre: `${i + 1}.jpg`, url: `/api/v1/carrusel/${i + 1}.jpg?v=1` }));
    vi.spyOn(globalThis, 'fetch').mockResolvedValue(new Response(JSON.stringify({ data }), { status: 200, headers: { 'Content-Type': 'application/json' } }));
}

function montar() {
    const queryClient = new QueryClient({ defaultOptions: { queries: { retry: false } } });
    return render(
        <QueryClientProvider client={queryClient}>
            <CarruselHero>
                <h1>Foldex</h1>
            </CarruselHero>
        </QueryClientProvider>,
    );
}

afterEach(() => vi.restoreAllMocks());

describe('carrusel de la portada', () => {
    it('crea un punto de navegación por cada foto de la carpeta', async () => {
        simularFotos(3);
        montar();

        expect(await screen.findByRole('button', { name: 'Ver foto 3' })).toBeInTheDocument();
        expect(screen.getAllByRole('button', { name: /^Ver foto/ })).toHaveLength(3);
        expect(screen.getByRole('heading', { name: 'Foldex' })).toBeInTheDocument();
    });

    it('avanza con la flecha', async () => {
        simularFotos(2);
        montar();

        expect(await screen.findByAltText('Comunidad UNAB, foto 1 de 2')).toBeInTheDocument();
        fireEvent.click(screen.getByRole('button', { name: 'Foto siguiente' }));
        expect(screen.getByAltText('Comunidad UNAB, foto 2 de 2')).toBeInTheDocument();
    });

    it('sin fotos muestra la ilustración y no muestra controles', async () => {
        simularFotos(0);
        montar();

        expect(await screen.findByAltText('Comunidad UNAB, foto 1 de 1')).toBeInTheDocument();
        expect(screen.queryByRole('button', { name: 'Foto siguiente' })).not.toBeInTheDocument();
    });
});
