<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mirrors the professor-provided `docentes` table from the institutional
 * schema (gestion_academica_utn) so a fresh environment reproduces the
 * same shape the real database already has.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('docentes')) {
            return;
        }

        Schema::create('docentes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
            $table->foreignId('puesto_id')->constrained('puestos')->restrictOnDelete();
            $table->string('cedula', 12)->unique();
            $table->string('nombre', 60);
            $table->string('primer_apellido', 60);
            $table->string('segundo_apellido', 60)->nullable();
            $table->decimal('jornada_estimada', 3, 2)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index(['primer_apellido', 'segundo_apellido']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('docentes');
    }
};
