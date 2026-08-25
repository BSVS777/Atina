<?php

namespace Tests\Unit\IdentityAccess;

use Src\IdentityAccess\Permission\Domain\Entities\Permission;
use Src\IdentityAccess\Permission\Domain\Exceptions\InvalidPermissionException;
use Src\IdentityAccess\Permission\Domain\Exceptions\PermissionIsProtectedException;
use Tests\TestCase;

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
}
