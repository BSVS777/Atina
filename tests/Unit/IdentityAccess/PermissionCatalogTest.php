<?php

namespace Tests\Unit\IdentityAccess;

use PHPUnit\Framework\TestCase;
use Src\IdentityAccess\Permission\Domain\ValueObjects\PermissionCatalog;

class PermissionCatalogTest extends TestCase
{
    public function test_modules_includes_both_manageable_and_institutional_modules(): void
    {
        $modules = PermissionCatalog::modules();

        $this->assertContains('roles', $modules);
        $this->assertContains('permissions', $modules);
        $this->assertContains('atinencia', $modules);
        $this->assertContains('nota_tecnica', $modules);
    }

    public function test_actions_for_a_manageable_module_returns_the_shared_crud_actions(): void
    {
        $this->assertSame(
            ['create', 'view', 'edit', 'delete', 'search', 'export_pdf', 'export_excel'],
            PermissionCatalog::actionsFor('roles'),
        );
    }

    public function test_actions_for_an_institutional_module_returns_its_own_actions_only(): void
    {
        $this->assertSame(['verificar'], PermissionCatalog::actionsFor('atinencia'));
        $this->assertSame(['gestionar', 'consultar', 'consolidar'], PermissionCatalog::actionsFor('oferta'));
    }

    public function test_actions_for_an_unknown_module_returns_an_empty_array(): void
    {
        $this->assertSame([], PermissionCatalog::actionsFor('unknown_module'));
        $this->assertSame([], PermissionCatalog::actionsFor(null));
    }

    public function test_is_official_accepts_a_real_combination(): void
    {
        $this->assertTrue(PermissionCatalog::isOfficial('atinencia', 'verificar'));
        $this->assertTrue(PermissionCatalog::isOfficial('roles', 'edit'));
    }

    public function test_is_official_rejects_an_action_not_valid_for_the_module(): void
    {
        // "aprobar" is only official under nota_tecnica, not catalogo.
        $this->assertFalse(PermissionCatalog::isOfficial('catalogo', 'aprobar'));
    }

    public function test_is_official_rejects_a_forged_module_or_action(): void
    {
        $this->assertFalse(PermissionCatalog::isOfficial('atinencia', 'delete_everything'));
        $this->assertFalse(PermissionCatalog::isOfficial('atinencias', 'verificar'));
    }

    public function test_is_official_is_case_sensitive_and_does_not_trim(): void
    {
        $this->assertFalse(PermissionCatalog::isOfficial('roles', 'Edit'));
        $this->assertFalse(PermissionCatalog::isOfficial('Roles', 'edit'));
        $this->assertFalse(PermissionCatalog::isOfficial('roles', ' edit'));
    }

    public function test_no_technical_permission_name_is_declared_twice(): void
    {
        // A duplicated module.action would let two rows claim the same
        // authorization contract and break the "closed catalog" premise.
        $names = [];

        foreach (PermissionCatalog::all() as $module => $actions) {
            foreach ($actions as $action) {
                $names[] = "{$module}.{$action}";
            }
        }

        $this->assertSame($names, array_values(array_unique($names)));
    }

    public function test_no_module_declares_the_same_action_twice(): void
    {
        foreach (PermissionCatalog::all() as $module => $actions) {
            $this->assertSame(
                array_values(array_unique($actions)),
                array_values($actions),
                "Module [{$module}] declares a duplicated action.",
            );
        }
    }

    public function test_every_declared_module_offers_at_least_one_action(): void
    {
        foreach (PermissionCatalog::modules() as $module) {
            $this->assertNotSame([], PermissionCatalog::actionsFor($module), "Module [{$module}] declares no action.");
        }
    }

    public function test_every_declared_combination_is_recognised_as_official(): void
    {
        foreach (PermissionCatalog::all() as $module => $actions) {
            foreach ($actions as $action) {
                $this->assertTrue(
                    PermissionCatalog::isOfficial($module, $action),
                    "[{$module}.{$action}] is declared but not recognised as official.",
                );
            }
        }
    }
}
