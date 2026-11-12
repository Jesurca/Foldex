<?php

namespace App\Models;

// Imports: factory, clase Model y relación hasMany.
use Database\Factories\MateriaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Materia del programa (tabla materias). Una materia tiene muchos proyectos. */
class Materia extends Model
{
    /** @use HasFactory<MateriaFactory> */
    use HasFactory;

    protected $table = 'materias';

    protected $fillable = ['codigo', 'nombre', 'semestre'];

    protected function casts(): array
    {
        return ['semestre' => 'integer'];
    }

    /** @return HasMany<Proyecto, $this> */
    public function proyectos(): HasMany
    {
        return $this->hasMany(Proyecto::class);
    }
}
