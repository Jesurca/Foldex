<?php

// Imports: clases de Laravel para definir el esquema (Migration, Blueprint, Schema).
use App\Enums\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Migración: Agrega a users el rol (estudiante/admin), el programa y el semestre.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->enum('role', Role::values())->default(Role::Estudiante->value)->after('password');
            $table->string('programa', 150)->nullable()->after('role');
            $table->unsignedTinyInteger('semestre')->nullable()->after('programa');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['role', 'programa', 'semestre']);
        });
    }
};
