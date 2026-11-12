/// <reference types="vite/client" />

// Tipos de las variables de entorno VITE_* (son públicas: terminan dentro del JavaScript compilado).

interface ImportMetaEnv {
    /** URL base de la API. Por defecto `/api/v1` (mismo origen que Laravel). */
    readonly VITE_API_URL?: string;
    readonly VITE_APP_NAME?: string;
}

interface ImportMeta {
    readonly env: ImportMetaEnv;
}
