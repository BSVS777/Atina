<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mirrors the professor-provided `asignaciones_docentes` table for the
 * columns this module actually uses. The real table also has jornada,
 * condicion_nombramiento, quincena, numero_accion_personal and
 * observacion (HR/payroll concerns owned by another module, not part of
 * DO-01/02/02a/02b/02d); jornada is NOT NULL there with no default, so
 * this module writes a fixed placeholder value and never reads it — see
 * Docs/DIARIO_DECISIONES_IA.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('asignaciones_docentes')) {
            return;
        }

        Schema::create('asignaciones_docentes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grupo_id')->constrained('grupos')->cascadeOnDelete();
            $table->foreignId('docente_id')->constrained('docentes')->restrictOnDelete();
            $table->decimal('jornada', 3, 2)->default(1.00);
            $table->enum('estado', ['Propuesta', 'Confirmada', 'Rechazada'])->default('Propuesta');
            $table->timestamps();

            $table->unique(['grupo_id', 'docente_id']);
            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asignaciones_docentes');
    }
};
