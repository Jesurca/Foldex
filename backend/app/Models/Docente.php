<?php

namespace App\Models;

// Imports: factory, clase Model y relación hasMany.
use Database\Factories\DocenteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Docente del catálogo (tabla docentes). Un docente dirige muchos proyectos. */
class Docente extends Model
{
    /** @use HasFactory<DocenteFactory> */
    use HasFactory;

    protected $table = 'docentes';

    protected $fillable = ['nombre', 'email'];

    /** @return HasMany<Proyecto, $this> */
    public function proyectos(): HasMany
    {
        return $this->hasMany(Proyecto::class);
    }
}
