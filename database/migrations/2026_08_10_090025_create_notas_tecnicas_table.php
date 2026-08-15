<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mirrors the professor-provided `notas_tecnicas` table (DO-02b). One
 * per assignment (unique) — D14: once "Vencida" it is terminal, a retry
 * requires starting a brand new assignment.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('notas_tecnicas')) {
            return;
        }

        Schema::create('notas_tecnicas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asignacion_docente_id')->unique()->constrained('asignaciones_docentes')->cascadeOnDelete();
            $table->foreignId('archivo_id')->constrained('archivos')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('fecha_limite_ratificacion');
            $table->enum('estado', ['Ratificación pendiente', 'Ratificada', 'Vencida', 'Rechazada'])->default('Ratificación pendiente');
            $table->timestamp('ratificada_at')->nullable();
            $table->timestamps();

            $table->index(['estado', 'fecha_limite_ratificacion']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notas_tecnicas');
    }
};
