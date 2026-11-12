// Configuración de Vitest (pruebas del frontend en frontend/js/test, con jsdom como navegador simulado).

// Imports: Vitest, el plugin de React y path.
import { defineConfig } from 'vitest/config';
import react from '@vitejs/plugin-react';
import path from 'node:path';

export default defineConfig({
    plugins: [react()],
    test: {
        environment: 'jsdom',
        globals: true,
        setupFiles: ['./frontend/js/test/setup.ts'],
        include: ['frontend/js/**/*.{test,spec}.{ts,tsx}'],
    },
    resolve: {
        alias: { '@': path.resolve(import.meta.dirname, 'frontend/js') },
    },
});
