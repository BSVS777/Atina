<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mirrors the professor-provided `atestados` table from the institutional
 * schema (gestion_academica_utn) so a fresh environment reproduces the
 * same shape the real database already has, including the native Spanish
 * `grado` ENUM (see DegreeLevelCast for the English translation boundary).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('atestados')) {
            return;
        }

        Schema::create('atestados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('docente_id')->constrained('docentes')->cascadeOnDelete();
            $table->foreignId('especialidad_id')->constrained('especialidades')->restrictOnDelete();
            $table->enum('grado', ['Diplomado', 'Bachillerato', 'Licenciatura', 'Maestría', 'Doctorado']);
            $table->string('institucion', 150);
            $table->unsignedSmallInteger('anio_obtencion');
            $table->timestamps();

            $table->unique(['docente_id', 'especialidad_id', 'grado'], 'atestados_docente_especialidad_grado_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('atestados');
    }
};
