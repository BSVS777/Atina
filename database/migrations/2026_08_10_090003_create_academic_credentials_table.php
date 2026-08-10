<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained()->cascadeOnDelete();
            $table->foreignId('specialty_id')->constrained()->restrictOnDelete();
            $table->string('degree_level');
            $table->string('institution');
            $table->unsignedSmallInteger('year_obtained');
            $table->timestamps();

            $table->unique(['teacher_id', 'specialty_id', 'degree_level'], 'academic_credentials_teacher_specialty_degree_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_credentials');
    }
};
