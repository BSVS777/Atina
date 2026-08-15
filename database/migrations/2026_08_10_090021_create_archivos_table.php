<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mirrors the professor-provided `archivos` table (institutional
 * schema) — the generic attachment mechanism used here only for the
 * Technical Note's signed PDF (DO-02b).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('archivos')) {
            return;
        }

        Schema::create('archivos', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('archivable_type', 120);
            $table->unsignedBigInteger('archivable_id');
            $table->string('tipo_documento', 60);
            $table->string('nombre_original', 255);
            $table->string('disco', 30)->default('local');
            $table->string('ruta', 255);
            $table->string('mime_type', 100);
            $table->unsignedInteger('tamano_bytes');
            $table->string('hash_sha256', 64);
            $table->timestamps();

            $table->unique(['disco', 'ruta']);
            $table->index(['archivable_type', 'archivable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('archivos');
    }
};
