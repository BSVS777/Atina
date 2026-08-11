<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mirrors the professor-provided `periodos_academicos` table
 * (institutional schema). `fecha_inicio` is exactly the "start date of
 * the destination quarter" the affinity catalog version resolution
 * algorithm (DO-02) compares against.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('periodos_academicos')) {
            return;
        }

        Schema::create('periodos_academicos', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('anio');
            $table->unsignedTinyInteger('cuatrimestre');
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->timestamps();

            $table->unique(['anio', 'cuatrimestre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('periodos_academicos');
    }
};
