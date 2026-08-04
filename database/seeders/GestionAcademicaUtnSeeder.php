<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Datos semilla compartidos por los 5 módulos (roles, permisos, carreras,
 * catálogos académicos). Fuente: sistema_gestion_academica_utn.sql,
 * extraído sin modificar en database/sql/seed_compartido.sql.
 */
class GestionAcademicaUtnSeeder extends Seeder
{
    public function run(): void
    {
        DB::unprepared(File::get(database_path('sql/seed_compartido.sql')));
    }
}
