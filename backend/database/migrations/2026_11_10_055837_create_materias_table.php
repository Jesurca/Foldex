<?php

// Imports: clases de Laravel para definir el esquema (Migration, Blueprint, Schema).
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Migración: Catálogo de materias del programa (código, nombre y semestre).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('materias', function (Blueprint $table): void {
            $table->id();
            $table->string('codigo', 20)->unique();
            $table->string('nombre', 150);
            $table->unsignedTinyInteger('semestre');
            $table->timestamps();

            $table->index('semestre');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('materias');
    }
};
