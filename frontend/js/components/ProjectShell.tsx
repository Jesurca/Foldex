// Estructura común de todas las páginas: barra institucional, encabezado con menú, modo oscuro, pie y el modal de ingreso/registro.

// Imports: router, iconos, avisos, botón, diálogo y la sesión (useAuth).
import { Link, useNavigate } from '@tanstack/react-router';
import { useEffect, useState, type FormEvent, type ReactNode } from 'react';
import { ArrowUpRight, Loader2, LogOut, Moon, Plus, Sun, UserRound } from 'lucide-react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogTitle } from '@/components/ui/dialog';
import { ApiError, mensajeDeError } from '@/lib/api/client';
import { correoPermitido, DOMINIO_PERMITIDO, useAuth } from '@/lib/auth';

// Logo "unab. Proyectos".
export function Brand() {
    return (
        <Link to="/" className="flex items-center gap-3">
            <span className="brand-word">
                unab<span className="text-primary">.</span>
            </span>
            <span className="h-8 border-l" />
            <span className="text-sm font-bold leading-tight">
                Proyectos<span className="block text-[10px] font-normal text-muted-foreground">INGENIERÍA DE SISTEMAS</span>
            </span>
        </Link>
    );
}

// Lee el tema guardado (claro/oscuro).
function leerTema(): boolean {
    try {
        return localStorage.getItem('unab-theme') === 'dark';
    } catch {
        return false;
    }
}

// Layout: encabezado + contenido de la página + pie.
export function ProjectShell({ children }: { children: ReactNode }) {
    const { usuario, esAdmin, salir, abrirModal } = useAuth();
    const navigate = useNavigate();
    const [dark, setDark] = useState(leerTema);

    useEffect(() => {
        document.documentElement.classList.toggle('dark', dark);
    }, [dark]);

    const toggle = () => {
        setDark(!dark);
        try {
            localStorage.setItem('unab-theme', dark ? 'light' : 'dark');
        } catch {
            /* sin almacenamiento: el tema dura la visita */
        }
    };

    const cerrarSesion = async () => {
        await salir();
        toast.success('Cerraste sesión');
        void navigate({ to: '/' });
    };

    return (
        <div className="min-h-screen bg-background">
            <div className="institution-bar">
                <div className="site-width flex items-center justify-between gap-3">
                    <span>Universidad Autónoma de Bucaramanga</span>
                    <span className="hidden sm:block">Facultad de Ingeniería · Comunidad académica</span>
                    <span className="sm:hidden">Ingeniería</span>
                </div>
            </div>
            <header className="site-header">
                <div className="site-width flex min-h-20 flex-wrap items-center justify-between gap-4 py-3">
                    <Brand />
                    <nav className="hidden items-center gap-7 text-sm font-semibold lg:flex">
                        <Link to="/" activeProps={{ className: 'text-primary' }} activeOptions={{ includeSearch: false }}>
                            Explorar proyectos
                        </Link>
                        <Link to="/mi-espacio" className="text-muted-foreground" activeProps={{ className: 'text-primary' }}>
                            Mi espacio
                        </Link>
                        {esAdmin && (
                            <Link to="/administracion" activeProps={{ className: 'text-primary' }}>
                                Administración
                            </Link>
                        )}
                    </nav>
                    <div className="flex items-center gap-2">
                        <Button variant="ghost" size="icon" onClick={toggle} aria-label={dark ? 'Activar modo claro' : 'Activar modo oscuro'} title="Cambiar tema">
                            {dark ? <Sun /> : <Moon />}
                        </Button>
                        {usuario ? (
                            <>
                                <Button variant="ghost" className="hidden sm:flex" asChild>
                                    <Link to="/mi-espacio">
                                        <span className="avatar-mini">{usuario.name.slice(0, 1)}</span>
                                        {usuario.name.split(' ')[0]}
                                    </Link>
                                </Button>
                                <Button variant="ghost" size="icon" title="Cerrar sesión" aria-label="Cerrar sesión" onClick={() => void cerrarSesion()}>
                                    <LogOut />
                                </Button>
                            </>
                        ) : (
                            <Button variant="ghost" onClick={() => abrirModal('entrar')}>
                                Ingresar
                            </Button>
                        )}
                        <Button className="hidden sm:flex" onClick={() => (usuario ? void navigate({ to: '/publicar' }) : abrirModal('entrar'))}>
                            <Plus /> Publicar proyecto
                        </Button>
                    </div>
                </div>
                <nav className="site-width flex gap-5 pb-3 text-sm lg:hidden">
                    <Link to="/">Explorar</Link>
                    <Link to="/mi-espacio">Mi espacio</Link>
                    <Link to="/publicar">Publicar</Link>
                    {esAdmin && <Link to="/administracion">Administración</Link>}
                </nav>
            </header>
            {children}
            <footer className="mt-16 border-t">
                <div className="site-width flex flex-wrap items-center justify-between gap-6 py-8">
                    <Brand />
                    <p className="text-xs text-muted-foreground">Proyecto académico · Ingeniería de Sistemas UNAB</p>
                    <span className="text-xs text-muted-foreground">
                        Hecho para compartir conocimiento <ArrowUpRight className="ml-1 inline size-3" />
                    </span>
                </div>
            </footer>
            <ModalAcceso />
        </div>
    );
}

type Errores = Record<string, string | undefined>;

// Mensaje de error debajo de un campo del formulario.
function ErrorCampo({ mensaje, id }: { mensaje: string | undefined; id: string }) {
    if (!mensaje) return null;
    return (
        <span id={id} className="mt-1 block text-xs font-normal text-destructive">
            {mensaje}
        </span>
    );
}

// Modal de ingreso y registro (POST /api/v1/login y /register).
function ModalAcceso() {
    const { modal, cerrarModal, cambiarModo, entrar, registrarse } = useAuth();
    const registro = modal.modo === 'registro';
    const [errores, setErrores] = useState<Errores>({});
    const [general, setGeneral] = useState<string | null>(null);
    const [enviando, setEnviando] = useState(false);

    useEffect(() => {
        setErrores({});
        setGeneral(null);
    }, [modal.modo, modal.abierto]);

    const enviar = async (e: FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        const f = new FormData(e.currentTarget);
        const valor = (k: string) => String(f.get(k) ?? '');
        const email = valor('email').trim().toLowerCase();

        if (!correoPermitido(email)) {
            setErrores({ email: `Solo se permiten correos institucionales @${DOMINIO_PERMITIDO}` });
            return;
        }
        if (registro && valor('password') !== valor('password_confirmation')) {
            setErrores({ password_confirmation: 'Las contraseñas no coinciden.' });
            return;
        }

        setEnviando(true);
        setErrores({});
        setGeneral(null);
        try {
            const usuario = registro
                ? await registrarse({
                      name: valor('name').trim(),
                      email,
                      password: valor('password'),
                      password_confirmation: valor('password_confirmation'),
                      programa: valor('programa').trim() || null,
                      semestre: valor('semestre') ? Number(valor('semestre')) : null,
                  })
                : await entrar(email, valor('password'));
            toast.success(registro ? `¡Bienvenido, ${usuario.name.split(' ')[0]}!` : `Hola de nuevo, ${usuario.name.split(' ')[0]}`);
            cerrarModal();
        } catch (error) {
            if (error instanceof ApiError && error.status === 422) {
                const porCampo: Errores = {};
                for (const [campo, mensajes] of Object.entries(error.errors)) porCampo[campo] = mensajes[0];
                setErrores(porCampo);
                if (!Object.keys(porCampo).length) setGeneral(error.message);
            } else {
                setGeneral(mensajeDeError(error));
            }
        } finally {
            setEnviando(false);
        }
    };

    const props = (campo: string) => ({
        name: campo,
        className: 'field mt-1',
        'aria-invalid': Boolean(errores[campo]),
        'aria-describedby': errores[campo] ? `error-${campo}` : undefined,
    });

    return (
        <Dialog open={modal.abierto} onOpenChange={(abierto) => (abierto ? cambiarModo(modal.modo) : cerrarModal())}>
            <DialogContent className="max-h-[92vh] max-w-md overflow-y-auto">
                <DialogTitle>{registro ? 'Crea tu cuenta' : 'Bienvenido a la comunidad'}</DialogTitle>
                <DialogDescription>Usa tu correo institucional @{DOMINIO_PERMITIDO}.</DialogDescription>
                <form onSubmit={(e) => void enviar(e)} className="grid gap-4" noValidate>
                    {registro && (
                        <label>
                            Nombre completo
                            <input {...props('name')} required minLength={3} maxLength={150} autoComplete="name" />
                            <ErrorCampo id="error-name" mensaje={errores['name']} />
                        </label>
                    )}
                    <label>
                        Correo institucional
                        <input {...props('email')} type="email" placeholder={`tu.nombre@${DOMINIO_PERMITIDO}`} required autoComplete="email" />
                        <ErrorCampo id="error-email" mensaje={errores['email']} />
                    </label>
                    <label>
                        Contraseña
                        <input {...props('password')} type="password" required autoComplete={registro ? 'new-password' : 'current-password'} />
                        {registro && !errores['password'] && (
                            <span className="mt-1 block text-xs text-muted-foreground">Mínimo 8 caracteres, con letras y números.</span>
                        )}
                        <ErrorCampo id="error-password" mensaje={errores['password']} />
                    </label>
                    {registro && (
                        <>
                            <label>
                                Confirma la contraseña
                                <input {...props('password_confirmation')} type="password" required autoComplete="new-password" />
                                <ErrorCampo id="error-password_confirmation" mensaje={errores['password_confirmation']} />
                            </label>
                            <div className="grid gap-4 sm:grid-cols-[1fr_120px]">
                                <label>
                                    Programa <span className="text-muted-foreground">(opcional)</span>
                                    <input {...props('programa')} defaultValue="Ingeniería de Sistemas" maxLength={150} />
                                    <ErrorCampo id="error-programa" mensaje={errores['programa']} />
                                </label>
                                <label>
                                    Semestre
                                    <select {...props('semestre')} defaultValue="">
                                        <option value="">—</option>
                                        {Array.from({ length: 10 }, (_, i) => i + 1).map((s) => (
                                            <option key={s} value={s}>
                                                {s}
                                            </option>
                                        ))}
                                    </select>
                                    <ErrorCampo id="error-semestre" mensaje={errores['semestre']} />
                                </label>
                            </div>
                        </>
                    )}
                    {general && (
                        <p className="rounded-md bg-destructive/10 px-3 py-2 text-sm text-destructive" role="alert">
                            {general}
                        </p>
                    )}
                    <Button type="submit" disabled={enviando}>
                        {enviando ? <Loader2 className="animate-spin" /> : null}
                        {registro ? 'Crear cuenta' : 'Ingresar'}
                        {!enviando && <ArrowUpRight />}
                    </Button>
                    <Button type="button" variant="link" onClick={() => cambiarModo(registro ? 'entrar' : 'registro')}>
                        {registro ? 'Ya tengo una cuenta' : '¿Primera vez? Crea tu cuenta'}
                    </Button>
                </form>
            </DialogContent>
        </Dialog>
    );
}

// Invitación a ingresar cuando una página exige sesión.
export function EmptyAccount() {
    const { abrirModal, cargando } = useAuth();
    if (cargando) return null;
    return (
        <div className="site-width py-20 text-center">
            <UserRound className="mx-auto size-12 text-primary" />
            <h1 className="mt-5 text-3xl font-bold">Tu espacio en la comunidad</h1>
            <p className="mt-3 text-muted-foreground">Ingresa para ver tus publicaciones y proyectos favoritos.</p>
            <Button className="mt-6" onClick={() => abrirModal('entrar')}>
                Ingresar
            </Button>
        </div>
    );
}
