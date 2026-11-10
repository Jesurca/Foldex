<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proyecto_tecnologia', function (Blueprint $table): void {
            $table->foreignId('proyecto_id')->constrained('proyectos')->cascadeOnDelete();
            $table->foreignId('tecnologia_id')->constrained('tecnologias')->restrictOnDelete();

            $table->primary(['proyecto_id', 'tecnologia_id']);
            $table->index('tecnologia_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proyecto_tecnologia');
    }
};
