import campus from '@/assets/campus-project.jpg';
import health from '@/assets/health-project.jpg';
import robot from '@/assets/robot-project.jpg';

export { campus };

const PORTADAS = [campus, health, robot] as const;

/** El backend no guarda imágenes: la portada es ilustrativa y estable para cada proyecto. */
export function portada(id: number): string {
    return PORTADAS[Math.abs(id) % PORTADAS.length] ?? campus;
}

export function fecha(iso: string | null | undefined): string {
    if (!iso) return '';
    return new Date(iso).toLocaleDateString('es-CO', { day: 'numeric', month: 'short', year: 'numeric' });
}

export function iniciales(nombre: string): string {
    return nombre
        .split(/\s+/)
        .filter(Boolean)
        .map((n) => n[0])
        .slice(0, 2)
        .join('')
        .toUpperCase();
}

/**
 * Convierte un enlace de YouTube o Google Drive en una URL embebible.
 * Cualquier otro dominio devuelve null y se muestra como enlace normal.
 */
export function urlVideoEmbebido(url: string | null | undefined): string | null {
    if (!url) return null;
    let u: URL;
    try {
        u = new URL(url);
    } catch {
        return null;
    }
    if (u.protocol !== 'https:') return null;
    const host = u.hostname.replace(/^www\.|^m\./, '');
    const idValido = (id: string | null | undefined) => (id && /^[\w-]{6,20}$/.test(id) ? id : null);

    if (host === 'youtube.com' || host === 'youtube-nocookie.com') {
        const partes = u.pathname.split('/').filter(Boolean);
        const id = u.pathname === '/watch' ? u.searchParams.get('v') : ['embed', 'shorts', 'live'].includes(partes[0] ?? '') ? partes[1] : null;
        const valido = idValido(id);
        return valido ? `https://www.youtube-nocookie.com/embed/${valido}` : null;
    }
    if (host === 'youtu.be') {
        const valido = idValido(u.pathname.slice(1));
        return valido ? `https://www.youtube-nocookie.com/embed/${valido}` : null;
    }
    if (host === 'drive.google.com') {
        const m = u.pathname.match(/^\/file\/d\/([\w-]{10,})/);
        return m ? `https://drive.google.com/file/d/${m[1]}/preview` : null;
    }
    return null;
}

export function metadata(titulo: string, descripcion: string) {
    return {
        meta: [
            { title: `${titulo} | UNAB Proyectos` },
            { name: 'description', content: descripcion },
            { property: 'og:title', content: `${titulo} | UNAB Proyectos` },
            { property: 'og:description', content: descripcion },
        ],
    };
}
