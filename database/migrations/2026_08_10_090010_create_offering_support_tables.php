<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Minimal mirror of three professor-provided scheduling/HR lookup tables
 * (`unidades_ejecutoras`, `metas`, `modalidades`) that this module does
 * not own or use for any business rule — they exist here only because
 * `grupos` has mandatory foreign keys into them. No Eloquent model is
 * built for these: they are bootstrap data for an out-of-scope module
 * (see Docs/DIARIO_DECISIONES_IA.md), referenced by raw id only.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('unidades_ejecutoras')) {
            Schema::create('unidades_ejecutoras', function (Blueprint $table) {
                $table->id();
                $table->string('codigo', 10)->unique();
                $table->string('nombre', 150);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('metas')) {
            Schema::create('metas', function (Blueprint $table) {
                $table->id();
                $table->foreignId('unidad_ejecutora_id')->constrained('unidades_ejecutoras')->restrictOnDelete();
                $table->string('codigo', 6);
                $table->string('nombre', 100);
                $table->timestamps();

                $table->unique(['unidad_ejecutora_id', 'codigo']);
            });
        }

        if (! Schema::hasTable('modalidades')) {
            Schema::create('modalidades', function (Blueprint $table) {
                $table->id();
                $table->string('nombre', 40)->unique();
                $table->boolean('requiere_resolucion')->default(false);
                $table->timestamps();
            });
        }

        if ((int) DB::table('unidades_ejecutoras')->count() === 0) {
            $unidadId = DB::table('unidades_ejecutoras')->insertGetId([
                'codigo' => '0000000000',
                'nombre' => 'Unidad Ejecutora de Desarrollo (bootstrap)',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('metas')->insert([
                'unidad_ejecutora_id' => $unidadId,
                'codigo' => '000001',
                'nombre' => 'Meta de desarrollo (bootstrap)',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if ((int) DB::table('modalidades')->count() === 0) {
            DB::table('modalidades')->insert([
                'nombre' => 'Presencial',
                'requiere_resolucion' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('metas');
        Schema::dropIfExists('modalidades');
        Schema::dropIfExists('unidades_ejecutoras');
    }
};
