// Pruebas del carrusel de la portada: lee las fotos de contexto_img/ y cambia de foto con los controles.
import { fireEvent, render, screen } from '@testing-library/react';
import { describe, expect, it } from 'vitest';
import { CarruselHero, FOTOS_CONTEXTO } from '@/components/CarruselHero';

describe('carrusel de la portada', () => {
    it('toma las fotos de la carpeta contexto_img', () => {
        expect(FOTOS_CONTEXTO.length).toBeGreaterThan(0);
    });

    it('muestra el texto de bienvenida y avanza con la flecha', () => {
        render(
            <CarruselHero>
                <h1>Proyectos UNAB</h1>
            </CarruselHero>,
        );
        expect(screen.getByRole('heading', { name: 'Proyectos UNAB' })).toBeInTheDocument();

        if (FOTOS_CONTEXTO.length > 1) {
            expect(screen.getByAltText(`Comunidad UNAB, foto 1 de ${FOTOS_CONTEXTO.length}`)).toBeInTheDocument();
            fireEvent.click(screen.getByRole('button', { name: 'Foto siguiente' }));
            expect(screen.getByAltText(`Comunidad UNAB, foto 2 de ${FOTOS_CONTEXTO.length}`)).toBeInTheDocument();
        }
    });
});
