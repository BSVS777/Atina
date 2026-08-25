<?php

declare(strict_types=1);

namespace Src\IdentityAccess\Permission\Domain\ValueObjects;

/**
 * The closed set of official (module, action) permission combinations —
 * the single source of truth this bounded context answers "is this a
 * real permission?" against. Two origins, merged here so no other layer
 * (Seeder, Form validation, Blade selects) has to know the difference:
 *
 * - MANAGEABLE_MODULES x MANAGEABLE_ACTIONS: SIGA's own admin UI
 *   (roles/permissions management), which follows the create/view/edit/...
 *   convention uniformly.
 * - INSTITUTIONAL_PERMISSIONS: the 16 business-verb permissions already
 *   seeded in the professor-provided `gestion_academica_utn_test`
 *   database (see PermissionSeeder, Docs/DIARIO_DECISIONES_IA.md) —
 *   external data this project does not control, reproduced verbatim.
 *
 * Pure PHP, zero framework coupling, so it can be consulted from Domain
 * (Permission entity invariants) as well as Presentation (Form
 * validation, Blade module/action selects) without any of them
 * duplicating the mapping independently.
 */
final class PermissionCatalog
{
    /** @var array<int, string> */
    private const MANAGEABLE_ACTIONS = [
        'create',
        'view',
        'edit',
        'delete',
        'search',
        'export_pdf',
        'export_excel',
    ];

    /** @var array<int, string> */
    private const MANAGEABLE_MODULES = ['roles', 'permissions'];

    /** @var array<string, array<int, string>> */
    private const INSTITUTIONAL_PERMISSIONS = [
        'atestados' => ['gestionar'],
        'catalogo' => ['gestionar'],
        'oferta' => ['gestionar', 'consultar', 'consolidar'],
        'atinencia' => ['verificar'],
        'nota_tecnica' => ['aprobar'],
        'usuarios' => ['gestionar'],
        'archivos' => ['subir', 'descargar'],
        'resoluciones' => ['gestionar'],
        'reservas' => ['gestionar'],
        'planes' => ['gestionar'],
        'equiparaciones' => ['gestionar'],
        'solicitudes' => ['crear', 'revisar'],
    ];

    /**
     * @return array<string, array<int, string>>
     */
    public static function all(): array
    {
        $manageable = array_fill_keys(self::MANAGEABLE_MODULES, self::MANAGEABLE_ACTIONS);

        return [...$manageable, ...self::INSTITUTIONAL_PERMISSIONS];
    }

    /**
     * @return array<int, string>
     */
    public static function modules(): array
    {
        return array_keys(self::all());
    }

    /**
     * @return array<int, string>
     */
    public static function actionsFor(?string $module): array
    {
        return self::all()[$module] ?? [];
    }

    public static function isOfficial(string $module, string $action): bool
    {
        return in_array($action, self::actionsFor($module), true);
    }
}
