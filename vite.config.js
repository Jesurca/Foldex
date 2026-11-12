// Configuración de Vite: compila el frontend (frontend/js y frontend/css) hacia public/build para Laravel.

// Imports: Vite, el plugin de Laravel, React, Tailwind, el generador de rutas de TanStack y path.
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import tailwindcss from '@tailwindcss/vite';
import { tanstackRouter } from '@tanstack/router-plugin/vite';
import path from 'node:path';

export default defineConfig({
    plugins: [
        // Genera frontend/js/routeTree.gen.ts a partir de los archivos en frontend/js/routes.
        // Debe ir antes del plugin de React.
        tanstackRouter({
            target: 'react',
            routesDirectory: './frontend/js/routes',
            generatedRouteTree: './frontend/js/routeTree.gen.ts',
            autoCodeSplitting: true,
        }),
        laravel({
            input: ['frontend/css/app.css', 'frontend/js/app.tsx'],
            // Recarga el navegador al cambiar la vista base o las rutas de Laravel.
            refresh: ['frontend/views/**', 'backend/routes/**', 'backend/lang/**'],
        }),
        react(),
        tailwindcss(),
    ],
    resolve: {
        alias: { '@': path.resolve(import.meta.dirname, 'frontend/js') },
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
