<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Replaces the single `anio_obtencion` year with `fecha_inicio` and
 * `fecha_fin` (start and end date of the studies). Existing rows keep
 * their year as both the start and end date so no data is lost — this
 * is a placeholder the teacher is expected to correct via the edit form,
 * since the exact day/month of a past year was never captured before.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('atestados', function (Blueprint $table) {
            $table->date('fecha_inicio')->nullable()->after('institucion');
            $table->date('fecha_fin')->nullable()->after('fecha_inicio');
        });

        DB::table('atestados')->whereNotNull('anio_obtencion')->orderBy('id')->each(function (object $row) {
            DB::table('atestados')->where('id', $row->id)->update([
                'fecha_inicio' => $row->anio_obtencion.'-01-01',
                'fecha_fin' => $row->anio_obtencion.'-12-31',
            ]);
        });

        Schema::table('atestados', function (Blueprint $table) {
            $table->date('fecha_inicio')->nullable(false)->change();
            $table->date('fecha_fin')->nullable(false)->change();
            $table->dropColumn('anio_obtencion');
        });
    }

    public function down(): void
    {
        Schema::table('atestados', function (Blueprint $table) {
            $table->unsignedSmallInteger('anio_obtencion')->nullable()->after('institucion');
        });

        DB::table('atestados')->whereNotNull('fecha_fin')->orderBy('id')->each(function (object $row) {
            DB::table('atestados')->where('id', $row->id)->update([
                'anio_obtencion' => (int) date('Y', strtotime($row->fecha_fin)),
            ]);
        });

        Schema::table('atestados', function (Blueprint $table) {
            $table->unsignedSmallInteger('anio_obtencion')->nullable(false)->change();
            $table->dropColumn(['fecha_inicio', 'fecha_fin']);
        });
    }
};
