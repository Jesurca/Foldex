import { QueryClient } from '@tanstack/react-query';
import { createRouter, rootRouteId } from '@tanstack/react-router';
import { describe, expect, it } from 'vitest';

import { routeTree } from '@/routeTree.gen';

// Solo resuelve rutas, sin renderizar ni llamar a la API.
describe('Rutas de la SPA', () => {
    const router = createRouter({ routeTree, context: { queryClient: new QueryClient() } });

    it.each(['/', '/proyectos/campus-connect', '/editar/campus-connect', '/publicar', '/mi-espacio', '/administracion'])('encuentra una página para %s', (ruta) => {
        expect(router.matchRoutes(ruta).at(-1)?.routeId).not.toBe(rootRouteId);
    });
});
