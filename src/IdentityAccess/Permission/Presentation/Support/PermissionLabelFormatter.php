<?php

declare(strict_types=1);

namespace Src\IdentityAccess\Permission\Presentation\Support;

/**
 * Turns a raw (module, action) pair into human-readable Spanish labels —
 * `forHumans()` composes the Role modal's permissions checklist phrase
 * (e.g. ('roles', 'edit') -> "Editar roles"); `moduleLabel()`/
 * `actionLabel()` back the standalone Módulo/Acción selects in the
 * Permission modal. Pure presentation formatting, no business rule
 * lives here — PermissionCatalog is the source of truth for which
 * combinations are valid. Falls back to a readable default for any
 * action/module not in the map, so new ones never render blank.
 */
final class PermissionLabelFormatter
{
    /** @var array<string, string> */
    private const ACTION_LABELS = [
        'create' => 'Crear',
        'view' => 'Ver',
        'edit' => 'Editar',
        'delete' => 'Eliminar',
        'search' => 'Buscar',
        'export_pdf' => 'Exportar PDF',
        'export_excel' => 'Exportar Excel',
        'gestionar' => 'Gestionar',
        'verificar' => 'Verificar',
        'aprobar' => 'Aprobar',
        'consultar' => 'Consultar',
        'consolidar' => 'Consolidar',
        'subir' => 'Subir',
        'descargar' => 'Descargar',
        'crear' => 'Crear',
        'revisar' => 'Revisar',
    ];

    /** @var array<string, string> */
    private const MODULE_LABELS = [
        'roles' => 'Roles',
        'permissions' => 'Permisos',
        'archivos' => 'Archivos',
        'atestados' => 'Atestados',
        'atinencia' => 'Atinencia',
        'catalogo' => 'Catálogo',
        'equiparaciones' => 'Equiparaciones',
        'nota_tecnica' => 'Nota técnica',
        'oferta' => 'Oferta académica',
        'planes' => 'Planes de estudio',
        'reservas' => 'Reservas',
        'resoluciones' => 'Resoluciones',
        'solicitudes' => 'Solicitudes',
        'usuarios' => 'Usuarios',
    ];

    public static function forHumans(string $module, string $action): string
    {
        // "Exportar PDF de roles" reads correctly in Spanish; every other
        // action phrase drops the preposition ("Editar roles").
        $preposition = str_starts_with($action, 'export_') ? ' de ' : ' ';

        return self::actionLabel($action).$preposition.mb_strtolower(self::moduleLabel($module));
    }

    public static function moduleLabel(string $module): string
    {
        return self::MODULE_LABELS[$module] ?? $module;
    }

    public static function actionLabel(string $action): string
    {
        return self::ACTION_LABELS[$action] ?? ucfirst(str_replace('_', ' ', $action));
    }
}
