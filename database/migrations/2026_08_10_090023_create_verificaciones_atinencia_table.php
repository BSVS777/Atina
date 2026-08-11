<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mirrors the professor-provided `verificaciones_atinencia` table
 * (DO-02a) — an append-only trail; each attempt/escalation inserts a new
 * row, none are ever updated (D11).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('verificaciones_atinencia')) {
            return;
        }

        Schema::create('verificaciones_atinencia', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asignacion_docente_id')->constrained('asignaciones_docentes')->cascadeOnDelete();
            $table->foreignId('catalogo_atinencia_id')->nullable()->constrained('catalogos_atinencia')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('resultado', ['Atinente', 'No Atinente', 'Nota técnica', 'Sin catálogo']);
            $table->boolean('es_provisional')->default(false);
            $table->text('justificacion')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['asignacion_docente_id', 'created_at']);
            $table->index('resultado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verificaciones_atinencia');
    }
};
