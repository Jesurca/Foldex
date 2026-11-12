<?php

// Imports: clases de Laravel para definir el esquema (Migration, Blueprint, Schema).
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Migración: Catálogo de docentes que dirigen proyectos.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('docentes', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre', 150);
            $table->string('email')->nullable()->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('docentes');
    }
};
