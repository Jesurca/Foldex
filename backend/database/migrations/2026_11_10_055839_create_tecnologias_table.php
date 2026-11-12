<?php

// Imports: clases de Laravel para definir el esquema (Migration, Blueprint, Schema).
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Migración: Catálogo de tecnologías (Laravel, React, PostgreSQL…).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tecnologias', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre', 60)->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tecnologias');
    }
};
