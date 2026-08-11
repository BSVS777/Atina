<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * The 9 roles already seeded in the professor-provided
     * `gestion_academica_utn_test` database (name => description),
     * reproduced verbatim so a fresh environment's RBAC substrate matches
     * production — see Docs/DIARIO_DECISIONES_IA.md. SIGA does not seed
     * its own separate "Admin" role: the official "Administrador" already
     * fills that purpose.
     *
     * @var array<string, string>
     */
    private const OFFICIAL_ROLES = [
        'Administrador' => 'Gestión total: catálogo de atinencias, usuarios y configuración',
        'Coordinadora de Docencia' => 'Registra atestados, consolida y gestiona asignaciones docentes',
        'Docente' => 'Consulta su perfil, atestados y asignaciones',
        'Consulta' => 'Acceso de solo lectura a la oferta académica',
        'Director de Carrera' => 'Registra la oferta, planes y resoluciones de su propia carrera',
        'Coordinador CONTA' => 'Consolida la oferta de las carreras de su área',
        'Recursos Humanos' => 'Lectura de la oferta consolidada; sin acceso a atinencias',
        'Estudiante' => 'Presenta y da seguimiento a sus propias solicitudes',
        'Comisión Técnica' => 'Revisa y resuelve solicitudes de convalidación',
    ];

    /**
     * Real role => permissions matrix from the official database.
     *
     * @var array<string, array<int, string>>
     */
    private const OFFICIAL_ROLE_PERMISSIONS = [
        'Administrador' => [
            'archivos.descargar', 'archivos.subir', 'atestados.gestionar', 'atinencia.verificar',
            'catalogo.gestionar', 'equiparaciones.gestionar', 'nota_tecnica.aprobar', 'oferta.consolidar',
            'oferta.consultar', 'oferta.gestionar', 'planes.gestionar', 'reservas.gestionar',
            'resoluciones.gestionar', 'solicitudes.crear', 'solicitudes.revisar', 'usuarios.gestionar',
        ],
        'Coordinadora de Docencia' => [
            'archivos.descargar', 'archivos.subir', 'atestados.gestionar', 'atinencia.verificar',
            'equiparaciones.gestionar', 'oferta.consolidar', 'oferta.consultar', 'oferta.gestionar',
            'planes.gestionar', 'reservas.gestionar', 'resoluciones.gestionar', 'solicitudes.revisar',
        ],
        'Comisión Técnica' => ['archivos.descargar', 'solicitudes.revisar'],
        'Consulta' => ['oferta.consultar'],
        'Coordinador CONTA' => ['archivos.descargar', 'oferta.consolidar', 'oferta.consultar'],
        'Director de Carrera' => [
            'archivos.descargar', 'archivos.subir', 'equiparaciones.gestionar',
            'oferta.consultar', 'oferta.gestionar', 'planes.gestionar', 'resoluciones.gestionar',
        ],
        'Docente' => ['archivos.descargar', 'oferta.consultar'],
        'Estudiante' => ['archivos.subir', 'solicitudes.crear'],
        'Recursos Humanos' => ['archivos.descargar', 'oferta.consultar'],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $superadmin = Role::query()->firstOrCreate(['name' => 'Superadmin']);
        $superadmin->permissions()->sync(Permission::query()->pluck('id'));

        foreach (self::OFFICIAL_ROLES as $name => $description) {
            $role = Role::query()->firstOrCreate(['name' => $name], ['description' => $description]);

            $permissionIds = Permission::query()
                ->whereIn('name', self::OFFICIAL_ROLE_PERMISSIONS[$name])
                ->pluck('id');

            $role->permissions()->syncWithoutDetaching($permissionIds);
        }
    }
}
