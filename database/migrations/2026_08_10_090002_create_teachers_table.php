<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->foreignId('position_id')->constrained()->restrictOnDelete();
            $table->string('national_id')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('second_last_name')->nullable();
            $table->decimal('estimated_workload', 3, 2)->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['last_name', 'second_last_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teachers');
    }
};
