<?php

namespace App\Models;

use Database\Factories\TecnologiaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tecnologia extends Model
{
    /** @use HasFactory<TecnologiaFactory> */
    use HasFactory;

    protected $table = 'tecnologias';

    protected $fillable = ['nombre'];

    /** @return BelongsToMany<Proyecto, $this> */
    public function proyectos(): BelongsToMany
    {
        return $this->belongsToMany(Proyecto::class, 'proyecto_tecnologia');
    }
}
