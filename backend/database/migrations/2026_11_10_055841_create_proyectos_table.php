<?php

// Imports: clases de Laravel para definir el esquema (Migration, Blueprint, Schema).
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Migración: Proyectos publicados. Borrado lógico (deleted_at) para que el admin modere sin perder evidencia.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proyectos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('materia_id')->constrained('materias')->restrictOnDelete();
            $table->foreignId('docente_id')->constrained('docentes')->restrictOnDelete();
            $table->string('titulo', 150);
            $table->string('slug', 180)->unique();
            $table->text('contexto');
            $table->string('repositorio_url', 500);
            $table->string('video_url', 500)->nullable();
            $table->string('periodo', 6);
            $table->timestamps();
            $table->softDeletes();

            $table->index('created_at');
            $table->index('periodo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proyectos');
    }
};
