<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mirrors the professor-provided `auditorias` table from the institutional
 * schema (gestion_academica_utn) so a fresh environment reproduces the
 * same shape the real database already has.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('auditorias')) {
            return;
        }

        Schema::create('auditorias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('auditable_type', 120);
            $table->unsignedBigInteger('auditable_id');
            $table->enum('accion', ['Creación', 'Modificación', 'Eliminación']);
            $table->json('cambios')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['auditable_type', 'auditable_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auditorias');
    }
};
