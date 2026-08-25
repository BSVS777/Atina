<?php

namespace Tests\Unit\IdentityAccess;

use Src\IdentityAccess\Permission\Domain\ValueObjects\PermissionCatalog;
use Tests\TestCase;

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
}
