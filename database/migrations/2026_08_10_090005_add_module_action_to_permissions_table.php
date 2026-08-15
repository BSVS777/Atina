<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The professor-provided `permissions` table (gestion_academica_utn) has
 * `name` + `description` only. SIGA's Role/Permission UI groups the
 * checklist by module, so this additively grants `module`/`action` and
 * backfills them for the pre-existing official rows by splitting `name`
 * on its first dot (e.g. "atestados.gestionar" -> module=atestados,
 * action=gestionar). Non-destructive: no official column or row is
 * altered or removed.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('permissions', 'module')) {
            Schema::table('permissions', function (Blueprint $table) {
                $table->string('module')->nullable()->after('name');
            });
        }

        if (! Schema::hasColumn('permissions', 'action')) {
            Schema::table('permissions', function (Blueprint $table) {
                $table->string('action')->nullable()->after('module');
            });
        }

        DB::table('permissions')->whereNull('module')->orWhereNull('action')->get(['id', 'name'])
            ->each(function (object $permission): void {
                [$module, $action] = array_pad(explode('.', (string) $permission->name, 2), 2, '');

                DB::table('permissions')->where('id', $permission->id)->update([
                    'module' => $module,
                    'action' => $action,
                ]);
            });
    }

    public function down(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->dropColumn(['module', 'action']);
        });
    }
};
