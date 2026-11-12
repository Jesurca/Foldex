// Ruta raíz de TanStack Router: envuelve todas las páginas con la sesión, los avisos (toasts) y las páginas de error/404.

// Imports: tipos de React Query, utilidades del router, avisos y el proveedor de sesión.
import type { QueryClient } from '@tanstack/react-query';
import { createRootRouteWithContext, HeadContent, Link, Outlet, useRouter, type ErrorComponentProps } from '@tanstack/react-router';
import { Toaster } from 'sonner';
import { AuthProvider } from '@/lib/auth';

// Página 404.
function NoEncontrada() {
    return (
        <div className="flex min-h-screen items-center justify-center bg-background px-4">
            <div className="max-w-md text-center">
                <h1 className="text-7xl font-bold text-foreground">404</h1>
                <h2 className="mt-4 text-xl font-semibold text-foreground">Página no encontrada</h2>
                <p className="mt-2 text-sm text-muted-foreground">La página que buscas no existe o fue movida.</p>
                <div className="mt-6">
                    <Link
                        to="/"
                        className="inline-flex items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90"
                    >
                        Volver al inicio
                    </Link>
                </div>
            </div>
        </div>
    );
}

// Página de error inesperado con botón para reintentar.
function ErrorGeneral({ error, reset }: ErrorComponentProps) {
    console.error(error);
    const router = useRouter();

    return (
        <div className="flex min-h-screen items-center justify-center bg-background px-4">
            <div className="max-w-md text-center">
                <h1 className="text-xl font-semibold tracking-tight text-foreground">No pudimos cargar esta página</h1>
                <p className="mt-2 text-sm text-muted-foreground">Algo salió mal. Puedes intentarlo de nuevo o volver al inicio.</p>
                <div className="mt-6 flex flex-wrap justify-center gap-2">
                    <button
                        onClick={() => {
                            void router.invalidate();
                            reset();
                        }}
                        className="inline-flex items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90"
                    >
                        Reintentar
                    </button>
                    <a
                        href="/"
                        className="inline-flex items-center justify-center rounded-md border border-input bg-background px-4 py-2 text-sm font-medium text-foreground transition-colors hover:bg-accent"
                    >
                        Volver al inicio
                    </a>
                </div>
            </div>
        </div>
    );
}

// Registro de la ruta raíz (recibe el QueryClient como contexto).
export const Route = createRootRouteWithContext<{ queryClient: QueryClient }>()({
    head: () => ({
        meta: [{ title: 'UNAB Proyectos' }, { name: 'description', content: 'Biblioteca de proyectos de Ingeniería de Sistemas' }],
    }),
    component: Raiz,
    notFoundComponent: NoEncontrada,
    errorComponent: ErrorGeneral,
});

// Contenedor de todas las páginas: sesión + página actual (Outlet) + avisos.
function Raiz() {
    return (
        <AuthProvider>
            <HeadContent />
            <Outlet />
            <Toaster position="top-center" richColors duration={2500} />
        </AuthProvider>
    );
}
