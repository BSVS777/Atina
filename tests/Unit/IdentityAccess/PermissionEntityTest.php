<?php

namespace Tests\Unit\IdentityAccess;

use PHPUnit\Framework\TestCase;
use Src\IdentityAccess\Permission\Domain\Entities\Permission;
use Src\IdentityAccess\Permission\Domain\Exceptions\InvalidPermissionException;
use Src\IdentityAccess\Permission\Domain\Exceptions\PermissionIsProtectedException;

class PermissionEntityTest extends TestCase
{
    public function test_create_accepts_an_official_module_action_combination(): void
    {
        $permission = Permission::create('atinencia', 'verificar');

        $this->assertSame('atinencia.verificar', $permission->name());
    }

    public function test_create_rejects_a_combination_outside_the_catalog(): void
    {
        $this->expectException(InvalidPermissionException::class);

        Permission::create('atinencia', 'delete_everything');
    }

    public function test_create_rejects_a_valid_action_under_the_wrong_module(): void
    {
        // "aprobar" is only official under nota_tecnica.
        $this->expectException(InvalidPermissionException::class);

        Permission::create('catalogo', 'aprobar');
    }

    public function test_reconstitute_does_not_validate_against_the_catalog(): void
    {
        // A pre-existing/legacy row outside the catalog must still load
        // (view, list, delete) — see PermissionCatalog and
        // Docs/DIARIO_DECISIONES_IA.md.
        $permission = Permission::reconstitute(99, 'legado', 'custom_action');

        $this->assertSame('legado.custom_action', $permission->name());
    }

    public function test_redefine_allows_saving_the_same_module_and_action(): void
    {
        $permission = Permission::reconstitute(1, 'atinencia', 'verificar');

        $permission->redefine('atinencia', 'verificar');

        $this->assertSame('atinencia', $permission->module());
        $this->assertSame('verificar', $permission->action());
    }

    public function test_redefine_rejects_changing_the_module(): void
    {
        $permission = Permission::reconstitute(1, 'atinencia', 'verificar');

        $this->expectException(PermissionIsProtectedException::class);

        $permission->redefine('roles', 'verificar');
    }

    public function test_redefine_rejects_changing_the_action(): void
    {
        $permission = Permission::reconstitute(1, 'atinencia', 'verificar');

        $this->expectException(PermissionIsProtectedException::class);

        $permission->redefine('atinencia', 'consultar');
    }

    public function test_a_newly_created_permission_has_no_persisted_identity_yet(): void
    {
        $this->assertNull(Permission::create('atinencia', 'verificar')->id());
        $this->assertSame(5, Permission::reconstitute(5, 'atinencia', 'verificar')->id());
    }

    public function test_the_technical_name_is_the_module_and_action_joined_by_a_dot(): void
    {
        $permission = Permission::create('nota_tecnica', 'aprobar');

        $this->assertSame('nota_tecnica', $permission->module());
        $this->assertSame('aprobar', $permission->action());
        $this->assertSame('nota_tecnica.aprobar', $permission->name());
    }

    public function test_an_official_permission_is_protected_from_deletion(): void
    {
        // Policies and RoleSeeder reference official permissions by name;
        // deleting one would silently break those checks.
        $this->assertTrue(Permission::create('atinencia', 'verificar')->isProtected());
        $this->assertTrue(Permission::reconstitute(1, 'roles', 'export_pdf')->isProtected());
    }

    public function test_a_legacy_permission_outside_the_catalog_is_not_protected(): void
    {
        $this->assertFalse(Permission::reconstitute(99, 'legado', 'custom_action')->isProtected());
    }
}
