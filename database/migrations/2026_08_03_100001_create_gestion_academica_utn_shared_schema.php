<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Tablas RBAC + académicas + atinencias + oferta + gestión documental
 * compartidas por los 5 módulos del sistema. Fuente: sistema_gestion_academica_utn.sql
 * (schema entregado por el profesor), extraído sin modificar en
 * database/sql/schema_compartido.sql. Las tablas de auth (users, sessions,
 * passkeys, etc.) ya las crea el scaffold estándar de Laravel — ver
 * 0001_01_01_000000_create_users_table.php y 2024_01_01_000000_create_passkeys_table.php.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(File::get(database_path('sql/schema_compartido.sql')));
    }

    public function down(): void
    {
        DB::unprepared('SET FOREIGN_KEY_CHECKS = 0;');

        $tables = [
            'auditorias', 'descargas_archivos', 'notas_tecnicas', 'archivos',
            'solicitud_estados_historial', 'solicitudes', 'convalidaciones_historicas', 'reglas_levantamiento',
            'reservas_aulas',
            'verificaciones_atinencia', 'asignacion_cambios', 'asignaciones_docentes', 'horarios', 'grupo_estados_historial', 'carrera_grupo', 'grupos',
            'atestados', 'docentes', 'puestos',
            'catalogo_atinencia_especialidad', 'catalogos_atinencia', 'especialidades',
            'historial_academico', 'estudiante_plan', 'estudiantes',
            'equiparaciones', 'requisitos', 'curso_nivel', 'niveles', 'planes_estudio',
            'resoluciones_modalidad', 'cursos', 'modalidades', 'aula_equipamiento', 'equipamientos', 'aulas', 'recintos', 'periodos_academicos', 'metas', 'unidades_ejecutoras', 'carreras',
            'permission_user', 'permission_role', 'role_user', 'permissions', 'roles',
        ];

        foreach ($tables as $table) {
            DB::statement("DROP TABLE IF EXISTS `{$table}`");
        }

        DB::unprepared('SET FOREIGN_KEY_CHECKS = 1;');
    }
};
