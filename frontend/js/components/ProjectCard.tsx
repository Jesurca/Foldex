import { Link } from '@tanstack/react-router';
import { ArrowUpRight, Heart, MessageCircle } from 'lucide-react';
import { Button } from '@/components/ui/button';
import type { Proyecto } from '@/lib/api/tipos';
import { useLike } from '@/lib/consultas';
import { fecha, iniciales, portada } from '@/lib/formato';

export function ProjectCard({ project: p }: { project: Proyecto }) {
    const alternarLike = useLike();

    return (
        <article className="project-card group">
            <Link to="/proyectos/$id" params={{ id: p.slug }} className="relative block overflow-hidden">
                <img
                    src={portada(p)}
                    alt={p.titulo}
                    width={1024}
                    height={768}
                    loading="lazy"
                    className="aspect-[1.85] w-full object-cover transition-transform duration-500 group-hover:scale-105"
                />
                <span className="absolute left-4 top-4 rounded bg-card/95 px-2.5 py-1 text-[11px] font-semibold">Semestre {p.materia.semestre}</span>
                <span className="absolute bottom-3 right-3 flex size-8 items-center justify-center rounded-full bg-card">
                    <ArrowUpRight className="size-4" />
                </span>
            </Link>
            <div className="p-5">
                <div className="mb-2 flex flex-wrap gap-1.5">
                    {p.tecnologias.slice(0, 3).map((t) => (
                        <span key={t.id} className="tech-tag">
                            {t.nombre}
                        </span>
                    ))}
                </div>
                <Link to="/proyectos/$id" params={{ id: p.slug }}>
                    <h3 className="text-xl font-bold transition-colors hover:text-primary">{p.titulo}</h3>
                </Link>
                <p className="mt-2 line-clamp-2 min-h-10 text-sm leading-5 text-muted-foreground">{p.contexto}</p>
                <div className="mt-5 flex items-center gap-2.5">
                    <span className="avatar-mini">{iniciales(p.autor.name)}</span>
                    <div>
                        <p className="text-xs font-semibold">{p.autor.name}</p>
                        <p className="mt-0.5 text-[11px] text-muted-foreground">{p.materia.nombre}</p>
                    </div>
                </div>
                <div className="mt-4 flex items-center justify-between border-t pt-3 text-xs text-muted-foreground">
                    <span>{fecha(p.publicado_en)}</span>
                    <div className="flex items-center gap-3">
                        <Button
                            variant="ghost"
                            size="sm"
                            className={p.liked ? 'text-primary' : 'text-muted-foreground'}
                            aria-label={`Me gusta ${p.titulo}`}
                            aria-pressed={p.liked ?? false}
                            onClick={() => alternarLike(p)}
                        >
                            <Heart className={p.liked ? 'fill-primary' : ''} />
                            {p.likes_count}
                        </Button>
                        <Link to="/proyectos/$id" params={{ id: p.slug }} hash="conversacion" className="flex items-center gap-1">
                            <MessageCircle className="size-3.5" />
                            {p.comentarios_count}
                        </Link>
                    </div>
                </div>
            </div>
        </article>
    );
}
