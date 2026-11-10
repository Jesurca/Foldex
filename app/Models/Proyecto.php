<?php

namespace App\Models;

use Database\Factories\ProyectoFactory;
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
