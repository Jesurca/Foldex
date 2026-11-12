<?php

// Imports: clases de Laravel para definir el esquema (Migration, Blueprint, Schema).
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Migración: Ruta de la foto de portada (opcional) dentro del disco public.
return new class extends Migration
{
    /** Ruta de la foto de portada dentro del disco `public` (null = se usa una ilustración). */
    public function up(): void
    {
        Schema::table('proyectos', function (Blueprint $table): void {
            $table->string('portada_path')->nullable()->after('video_url');
        });
    }

    public function down(): void
    {
        Schema::table('proyectos', function (Blueprint $table): void {
            $table->dropColumn('portada_path');
        });
    }
};
