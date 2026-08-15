<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mirrors the professor-provided `grupos` table for the columns this
 * module actually uses. The real table also has aula_id, matricula_*
 * and estado (room/scheduling concerns owned by another, out-of-scope
 * module); meta_id/modalidad_id/cupo are mandatory there but carry no
 * business meaning for affinity verification — kept only to satisfy the
 * official foreign keys, never read by this module's domain/UI. See
 * Docs/DIARIO_DECISIONES_IA.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('grupos')) {
            return;
        }

        Schema::create('grupos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curso_id')->constrained('cursos')->restrictOnDelete();
            $table->foreignId('periodo_academico_id')->constrained('periodos_academicos')->restrictOnDelete();
            $table->foreignId('meta_id')->constrained('metas')->restrictOnDelete();
            $table->foreignId('modalidad_id')->constrained('modalidades')->restrictOnDelete();
            $table->unsignedTinyInteger('numero');
            $table->unsignedSmallInteger('cupo')->default(30);
            $table->timestamps();

            $table->unique(['curso_id', 'periodo_academico_id', 'numero']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grupos');
    }
};
