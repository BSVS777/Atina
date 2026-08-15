<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * IMPLEMENT_ACADEMIC_AFFINITIES.md §13 requires a verification record to
 * preserve "which academic credential was considered". The
 * professor-provided `verificaciones_atinencia` does not have this
 * column, so this additively (nullable, non-destructive) adds it —
 * unused when the result is not "Atinente".
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('verificaciones_atinencia', 'atestado_id')) {
            return;
        }

        Schema::table('verificaciones_atinencia', function (Blueprint $table) {
            $table->foreignId('atestado_id')->nullable()->after('catalogo_atinencia_id')->constrained('atestados')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('verificaciones_atinencia', function (Blueprint $table) {
            $table->dropConstrainedForeignId('atestado_id');
        });
    }
};
