<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mirrors the professor-provided `carreras` table (institutional schema)
 * so a fresh environment reproduces the same shape the real database
 * already has.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('carreras')) {
            return;
        }

        Schema::create('carreras', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150)->unique();
            $table->boolean('activa')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carreras');
    }
};
