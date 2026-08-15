<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mirrors the professor-provided `cursos` table for the columns this
 * module actually uses. The real table also has es_servicio,
 * es_cuello_botella, requiere_laboratorio and tipo_laboratorio
 * (scheduling/curriculum concerns owned by another module); this module
 * intentionally scopes every course to exactly one career and does not
 * model transversal/service courses — see Docs/DIARIO_DECISIONES_IA.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cursos')) {
            return;
        }

        Schema::create('cursos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('carrera_id')->constrained('carreras')->restrictOnDelete();
            $table->string('codigo', 30)->unique();
            $table->string('nombre', 150);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cursos');
    }
};
