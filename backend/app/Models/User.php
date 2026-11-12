<?php

namespace App\Models;

// Imports: rol (enum), factory, relaciones, usuario autenticable de Laravel y tokens de Sanctum.
use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/** Usuario (tabla users). HasApiTokens le da los tokens Bearer que usan la SPA y Claude por MCP. */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /** `role` queda fuera a propósito: nadie se vuelve admin por asignación masiva. */
    protected $fillable = [
        'name',
        'email',
        'password',
        'programa',
        'semestre',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $attributes = [
        'role' => 'estudiante',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
            'semestre' => 'integer',
        ];
    }

    public function esAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    /** @return HasMany<Proyecto, $this> */
    public function proyectos(): HasMany
    {
        return $this->hasMany(Proyecto::class);
    }

    /** @return HasMany<Comentario, $this> */
    public function comentarios(): HasMany
    {
        return $this->hasMany(Comentario::class);
    }

    /** Proyectos a los que el usuario dio like. */
    public function likes(): BelongsToMany
    {
        return $this->belongsToMany(Proyecto::class, 'likes')->withPivot('created_at');
    }
}
