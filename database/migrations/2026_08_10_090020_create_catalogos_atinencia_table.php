<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mirrors the professor-provided `catalogos_atinencia` +
 * `catalogo_atinencia_especialidad` tables (institutional schema) —
 * DO-02's versioned affinity catalog. Each row is one immutable version;
 * "updating" the catalog always inserts a new row (see
 * CreateAffinityCatalogVersionUseCase).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('catalogos_atinencia')) {
            Schema::create('catalogos_atinencia', function (Blueprint $table) {
                $table->id();
                $table->foreignId('curso_id')->constrained('cursos')->cascadeOnDelete();
                $table->unsignedSmallInteger('version')->default(1);
                $table->string('acuerdo', 120);
                $table->string('numero_gaceta', 60);
                $table->date('vigencia_inicio');
                $table->date('vigencia_fin')->nullable();
                $table->timestamps();

                $table->unique(['curso_id', 'version']);
                $table->index(['curso_id', 'vigencia_inicio']);
            });
        }

        if (! Schema::hasTable('catalogo_atinencia_especialidad')) {
            Schema::create('catalogo_atinencia_especialidad', function (Blueprint $table) {
                $table->foreignId('catalogo_atinencia_id')->constrained('catalogos_atinencia')->cascadeOnDelete();
                $table->foreignId('especialidad_id')->constrained('especialidades')->restrictOnDelete();

                $table->primary(['catalogo_atinencia_id', 'especialidad_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('catalogo_atinencia_especialidad');
        Schema::dropIfExists('catalogos_atinencia');
    }
};
