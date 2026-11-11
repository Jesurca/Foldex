<?php

namespace App\Models;

use Database\Factories\ProyectoFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Proyecto extends Model
{
    /** @use HasFactory<ProyectoFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'proyectos';

    /** `user_id` y `slug` quedan fuera: el autor sale del token y el slug lo genera el sistema. */
    protected $fillable = [
        'materia_id',
        'docente_id',
        'titulo',
        'contexto',
        'repositorio_url',
        'video_url',
        'periodo',
    ];

    protected static function booted(): void
    {
        static::creating(function (Proyecto $proyecto): void {
            $proyecto->slug ??= static::slugUnico($proyecto->titulo);
        });
    }

    /** Las rutas usan el slug (`/proyectos/campus-connect`) en vez del id. */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Carga lo que necesita `ProyectoResource`: relaciones, contadores y si el
     * usuario actual ya dio like (una sola consulta extra, sin N+1).
     *
     * @param  Builder<Proyecto>  $query
     */
    public function scopeConDetalles(Builder $query, ?User $usuario): void
    {
        $query->with(['user', 'materia', 'docente', 'tecnologias'])
            ->withCount(['likes', 'comentarios'])
            ->when($usuario, fn (Builder $q) => $q->withExists([
                'likes as liked_by_me' => fn (Builder $l) => $l->where('users.id', $usuario->id),
            ]));
    }

    /**
     * Filtros del catálogo. Todos se aplican en SQL.
     *
     * @param  Builder<Proyecto>  $query
     * @param  array<string, mixed>  $filtros
     */
    public function scopeFiltrar(Builder $query, array $filtros): void
    {
        $texto = trim((string) ($filtros['q'] ?? ''));

        $query
            ->when($texto !== '', fn (Builder $q) => $q->where(function (Builder $w) use ($texto): void {
                $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $texto).'%';
                $w->where('titulo', 'ilike', $like)
                    ->orWhere('contexto', 'ilike', $like)
                    ->orWhereHas('user', fn (Builder $u) => $u->where('name', 'ilike', $like))
                    ->orWhereHas('tecnologias', fn (Builder $t) => $t->where('nombre', 'ilike', $like));
            }))
            ->when($filtros['materia'] ?? null, fn (Builder $q, $id) => $q->where('materia_id', $id))
            ->when($filtros['docente'] ?? null, fn (Builder $q, $id) => $q->where('docente_id', $id))
            ->when($filtros['tecnologia'] ?? null, fn (Builder $q, $id) => $q->whereHas(
                'tecnologias', fn (Builder $t) => $t->where('tecnologias.id', $id)
            ))
            ->when($filtros['semestre'] ?? null, fn (Builder $q, $s) => $q->whereHas(
                'materia', fn (Builder $m) => $m->where('semestre', $s)
            ))
            ->when($filtros['periodo'] ?? null, fn (Builder $q, $p) => $q->where('periodo', $p));

        // Ranking: likes recibidos en la ventana elegida (último día, semana o año).
        $desde = match ($filtros['rango'] ?? null) {
            'diario' => now()->subDay(),
            'semanal' => now()->subWeek(),
            'anual' => now()->subYear(),
            default => null,
        };

        if ($desde) {
            $query->withCount(['likes as likes_rango_count' => fn (Builder $l) => $l->where('likes.created_at', '>=', $desde)])
                ->orderByDesc('likes_rango_count')
                ->orderByDesc('likes_count');
        } elseif (($filtros['orden'] ?? 'recientes') === 'populares') {
            $query->orderByDesc('likes_count');
        }

        $query->latest()->orderByDesc('id');
    }

    public static function slugUnico(string $titulo): string
    {
        $base = Str::slug($titulo) ?: 'proyecto';
        $slug = $base;
        $i = 2;

        while (static::withTrashed()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Materia, $this> */
    public function materia(): BelongsTo
    {
        return $this->belongsTo(Materia::class);
    }

    /** @return BelongsTo<Docente, $this> */
    public function docente(): BelongsTo
    {
        return $this->belongsTo(Docente::class);
    }

    /** @return BelongsToMany<Tecnologia, $this> */
    public function tecnologias(): BelongsToMany
    {
        return $this->belongsToMany(Tecnologia::class, 'proyecto_tecnologia');
    }

    /** @return HasMany<Comentario, $this> */
    public function comentarios(): HasMany
    {
        return $this->hasMany(Comentario::class);
    }

    /** Usuarios que dieron like al proyecto. */
    public function likes(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'likes')->withPivot('created_at');
    }
}
