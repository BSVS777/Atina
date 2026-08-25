<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;
use Src\IdentityAccess\Permission\Domain\ValueObjects\PermissionCatalog;

class PermissionSeeder extends Seeder
{
    /**
     * Human-readable descriptions for the institutional permissions,
     * verbatim from the professor-provided `gestion_academica_utn_test`
     * database — see Docs/DIARIO_DECISIONES_IA.md. Permissions without an
     * entry here (SIGA's own roles/permissions admin actions) seed with
     * no description.
     *
     * @var array<string, string>
     */
    private const DESCRIPTIONS = [
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

    /**
     * Seeds exactly the closed set PermissionCatalog describes — the
     * catalog is the single source of truth for which (module, action)
     * pairs exist; this seeder no longer keeps its own copy of that list.
     */
    public function run(): void
    {
        foreach (PermissionCatalog::all() as $module => $actions) {
            foreach ($actions as $action) {
                $name = "{$module}.{$action}";

                Permission::query()->firstOrCreate(
                    ['name' => $name],
                    ['module' => $module, 'action' => $action, 'description' => self::DESCRIPTIONS[$name] ?? null],
                );
            }
        }
    }
}
