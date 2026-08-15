<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mirrors the professor-provided `puestos` table from the institutional
 * schema (gestion_academica_utn) so a fresh environment reproduces the
 * same shape the real database already has.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('puestos')) {
            return;
        }

        Schema::create('puestos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 60)->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('puestos');
    }
};
