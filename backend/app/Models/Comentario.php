<?php

namespace App\Models;

use Database\Factories\ComentarioFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comentario extends Model
{
    /** @use HasFactory<ComentarioFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'comentarios';

    /** `user_id` y `proyecto_id` se asignan desde el usuario autenticado y la ruta. */
    protected $fillable = ['contenido'];

    /** @return BelongsTo<Proyecto, $this> */
    public function proyecto(): BelongsTo
    {
        return $this->belongsTo(Proyecto::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
