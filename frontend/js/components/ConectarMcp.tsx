// Botón "Conéctate por MCP": genera un token personal y muestra cómo agregar el servidor
// MCP del repositorio (POST /mcp/proyectos en el backend) a Claude Code o Claude Desktop.
// Con eso, el estudiante publica desde Claude con un prompt: solo da el repositorio y el docente.

// Imports: React Query (tokens), React (estado del diálogo), iconos, avisos y piezas propias.
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { useState } from 'react';
import { Bot, Check, Copy, KeyRound, Loader2, Plug, Trash2 } from 'lucide-react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogTitle } from '@/components/ui/dialog';
import { mensajeDeError } from '@/lib/api/client';
import { perfil } from '@/lib/api/servicios';
import { useAuth } from '@/lib/auth';
import { fecha } from '@/lib/formato';

/** Nombre con el que se guarda el token; sirve para listar solo las conexiones de IA. */
export const NOMBRE_TOKEN_MCP = 'Claude (MCP)';
const NOMBRE_SERVIDOR = 'proyectos-unab';

/** URL del servidor MCP: mismo dominio que la página (ver routes/ai.php). */
export function urlServidorMcp(origen = window.location.origin): string {
    return `${origen}/mcp/proyectos`;
}

/** Comando para Claude Code (terminal). */
export function comandoClaudeCode(url: string, token: string): string {
    return `claude mcp add --transport http ${NOMBRE_SERVIDOR} ${url} --header "Authorization: Bearer ${token}"`;
}

/**
 * Configuración para Claude Desktop. Claude Desktop habla con servidores remotos a través de
 * `mcp-remote` (requiere Node.js). El token va en una variable de entorno para evitar
 * problemas con los espacios en Windows; `--allow-http` solo se agrega si el sitio no usa https.
 */
export function configClaudeDesktop(url: string, token: string): string {
    const args = ['-y', 'mcp-remote', url, '--header', 'Authorization:${AUTH_HEADER}'];
    if (url.startsWith('http://') && !/^http:\/\/(localhost|127\.0\.0\.1)/.test(url)) args.push('--allow-http');
    return JSON.stringify({ mcpServers: { [NOMBRE_SERVIDOR]: { command: 'npx', args, env: { AUTH_HEADER: `Bearer ${token}` } } } }, null, 2);
}

/** Prompt de ejemplo: lo único obligatorio es el repositorio y el docente; el publicante es la cuenta conectada. */
const PROMPT_EJEMPLO = 'Publica mi proyecto en el Repositorio de Proyectos UNAB.\nRepositorio: https://github.com/usuario/mi-proyecto\nDocente: Fabián Suárez';

export function BotonConectarMcp({ className, variante = 'default' }: { className?: string; variante?: 'default' | 'outline' }) {
    const { usuario, abrirModal } = useAuth();
    const [abierto, setAbierto] = useState(false);

    return (
        <>
            <Button variant={variante} className={className} onClick={() => (usuario ? setAbierto(true) : abrirModal('entrar'))}>
                <Plug />
                Conéctate por MCP
            </Button>
            <Dialog open={abierto} onOpenChange={setAbierto}>
                <DialogContent className="max-h-[92vh] max-w-2xl overflow-y-auto">{abierto && <PasosConexion />}</DialogContent>
            </Dialog>
        </>
    );
}

function PasosConexion() {
    const queryClient = useQueryClient();
    const [token, setToken] = useState<string | null>(null);
    const [cliente, setCliente] = useState<'code' | 'desktop'>('code');
    const url = urlServidorMcp();

    // --- Conexiones existentes (tokens con el nombre de MCP) ---
    const tokens = useQuery({ queryKey: ['tokens'], queryFn: perfil.tokens });
    const conexiones = (tokens.data ?? []).filter((t) => t.nombre === NOMBRE_TOKEN_MCP);

    // --- Crear y revocar tokens (POST / DELETE /api/v1/me/tokens) ---
    const crear = useMutation({
        mutationFn: () => perfil.crearToken(NOMBRE_TOKEN_MCP),
        onSuccess: (r) => {
            setToken(r.token);
            void queryClient.invalidateQueries({ queryKey: ['tokens'] });
        },
        onError: (e) => toast.error(mensajeDeError(e)),
    });
    const revocar = useMutation({
        mutationFn: (id: number) => perfil.eliminarToken(id),
        onSuccess: () => {
            toast.success('Conexión revocada');
            void queryClient.invalidateQueries({ queryKey: ['tokens'] });
        },
        onError: (e) => toast.error(mensajeDeError(e)),
    });

    return (
        <>
            <DialogTitle className="flex items-center gap-2">
                <Bot className="size-5 text-primary" />
                Publica desde Claude con MCP
            </DialogTitle>
            <DialogDescription>
                Conecta Claude a tu cuenta. Luego solo le das el enlace del repositorio y el nombre del docente: Claude lee el repositorio y completa la descripción, la materia, las
                tecnologías y la foto. El proyecto se publica a tu nombre.
            </DialogDescription>

            {/* Paso 1: token */}
            <section className="mt-2 space-y-2">
                <h3 className="text-sm font-bold">1. Genera tu llave de conexión</h3>
                {token ? (
                    <p className="text-xs text-muted-foreground">Llave creada. Ya va incluida en los pasos de abajo; no se volverá a mostrar.</p>
                ) : (
                    <Button onClick={() => crear.mutate()} disabled={crear.isPending}>
                        {crear.isPending ? <Loader2 className="animate-spin" /> : <KeyRound />}
                        Generar llave
                    </Button>
                )}
            </section>

            {/* Paso 2: agregar el servidor en Claude */}
            <section className="mt-4 space-y-2">
                <h3 className="text-sm font-bold">2. Agrega el servidor en Claude</h3>
                <div role="tablist" className="flex gap-2">
                    {(['code', 'desktop'] as const).map((c) => (
                        <Button key={c} role="tab" size="sm" aria-selected={cliente === c} variant={cliente === c ? 'secondary' : 'ghost'} onClick={() => setCliente(c)}>
                            {c === 'code' ? 'Claude Code' : 'Claude Desktop'}
                        </Button>
                    ))}
                </div>
                {cliente === 'code' ? (
                    <>
                        <p className="text-xs text-muted-foreground">Ejecuta en tu terminal:</p>
                        <Codigo texto={comandoClaudeCode(url, token ?? 'TU_LLAVE')} />
                    </>
                ) : (
                    <>
                        <p className="text-xs text-muted-foreground">
                            En Claude Desktop: Configuración → Desarrollador → Editar configuración, pega esto en <code>claude_desktop_config.json</code> y reinicia Claude (necesita
                            Node.js).
                        </p>
                        <Codigo texto={configClaudeDesktop(url, token ?? 'TU_LLAVE')} />
                    </>
                )}
            </section>

            {/* Paso 3: prompt */}
            <section className="mt-4 space-y-2">
                <h3 className="text-sm font-bold">3. Pídeselo a Claude</h3>
                <Codigo texto={PROMPT_EJEMPLO} />
            </section>

            {/* Conexiones activas */}
            {conexiones.length > 0 && (
                <section className="mt-4 border-t pt-4">
                    <h3 className="text-sm font-bold">Conexiones activas</h3>
                    <ul className="mt-2 space-y-2">
                        {conexiones.map((t) => (
                            <li key={t.id} className="flex items-center justify-between gap-3 text-xs">
                                <span className="text-muted-foreground">
                                    Creada {fecha(t.creado_en)} · {t.ultimo_uso ? `último uso ${fecha(t.ultimo_uso)}` : 'sin usar'}
                                </span>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    className="text-destructive"
                                    disabled={revocar.isPending}
                                    onClick={() => {
                                        if (window.confirm('¿Revocar esta conexión? Claude dejará de poder publicar con ella.')) revocar.mutate(t.id);
                                    }}
                                >
                                    <Trash2 />
                                    Revocar
                                </Button>
                            </li>
                        ))}
                    </ul>
                </section>
            )}
        </>
    );
}

/** Bloque de código con botón de copiar. */
function Codigo({ texto }: { texto: string }) {
    const [copiado, setCopiado] = useState(false);
    const copiar = async () => {
        try {
            await navigator.clipboard.writeText(texto);
            setCopiado(true);
            setTimeout(() => setCopiado(false), 1500);
        } catch {
            toast.error('No fue posible copiar');
        }
    };
    return (
        <div className="relative">
            <pre className="overflow-x-auto whitespace-pre-wrap break-all rounded-md border bg-muted p-3 pr-12 text-xs">{texto}</pre>
            <Button size="icon" variant="ghost" className="absolute right-1 top-1 size-8" aria-label="Copiar" onClick={() => void copiar()}>
                {copiado ? <Check /> : <Copy />}
            </Button>
        </div>
    );
}
