<?php

namespace App\Models;

use Database\Factories\MateriaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
