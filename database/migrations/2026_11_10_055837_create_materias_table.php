<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
