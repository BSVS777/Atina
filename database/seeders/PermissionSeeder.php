<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Actions available for every manageable module.
     *
     * @var array<int, string>
     */
    private const ACTIONS = [
        'create',
        'view',
        'edit',
        'delete',
        'search',
        'export_pdf',
        'export_excel',
    ];

    /**
     * SIGA-specific manageable modules (its own admin UI, not part of the
     * professor-provided institutional schema). Extend as new manageable
     * modules are added.
     *
     * @var array<int, string>
     */
    private const MODULES = ['roles', 'permissions'];

    /**
     * The 16 permissions already seeded in the professor-provided
     * `gestion_academica_utn_test` database (name => description),
     * reproduced verbatim so a fresh environment's RBAC substrate matches
     * production. Business-verb names (not SIGA's create/view/edit/...
     * convention) because they are external data, not code this project
     * controls — see Docs/DIARIO_DECISIONES_IA.md.
     *
     * @var array<string, string>
     */
    private const OFFICIAL_PERMISSIONS = [
        'atestados.gestionar' => 'Crear y editar atestados de docentes',
        'catalogo.gestionar' => 'Crear versiones del catálogo de atinencias',
        'oferta.gestionar' => 'Gestionar la oferta académica',
        'atinencia.verificar' => 'Ejecutar verificaciones de atinencia',
        'nota_tecnica.aprobar' => 'Aprobar la vía excepcional de Nota Técnica',
        'oferta.consultar' => 'Consultar la oferta académica',
        'usuarios.gestionar' => 'Gestionar usuarios del sistema',
        'archivos.subir' => 'Subir archivos',
        'archivos.descargar' => 'Descargar archivos',
        'resoluciones.gestionar' => 'Gestionar resoluciones',
        'reservas.gestionar' => 'Gestionar reservas',
        'oferta.consolidar' => 'Consolidar la oferta académica',
        'planes.gestionar' => 'Gestionar planes de estudio',
        'equiparaciones.gestionar' => 'Gestionar equiparaciones',
        'solicitudes.crear' => 'Crear solicitudes',
        'solicitudes.revisar' => 'Revisar solicitudes',
    ];

    public function run(): void
    {
        foreach (self::MODULES as $module) {
            foreach (self::ACTIONS as $action) {
                Permission::query()->firstOrCreate(
                    ['name' => "{$module}.{$action}"],
                    ['module' => $module, 'action' => $action],
                );
            }
        }

        foreach (self::OFFICIAL_PERMISSIONS as $name => $description) {
            [$module, $action] = explode('.', $name, 2);

            Permission::query()->firstOrCreate(
                ['name' => $name],
                ['module' => $module, 'action' => $action, 'description' => $description],
            );
        }
    }
}
