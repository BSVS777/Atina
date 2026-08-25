<?php

namespace Tests\Unit\IdentityAccess;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Src\IdentityAccess\Role\Domain\Entities\Role;
use Src\IdentityAccess\Role\Domain\Exceptions\RoleIsProtectedException;

/**
 * Structural roles (Superadmin, Administrador) are referenced by seeders
 * and `Gate::before`, so renaming one would silently detach those
 * references. Their permission set stays editable.
 */
class RoleEntityTest extends TestCase
{
    #[DataProvider('protectedRoleNames')]
    public function test_a_structural_role_is_protected(string $name): void
    {
        $this->assertTrue(Role::reconstitute(1, $name)->isProtected());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function protectedRoleNames(): array
    {
        return [
            'superadmin' => ['Superadmin'],
            'administrador' => ['Administrador'],
        ];
    }

    public function test_a_role_created_by_the_user_is_not_protected(): void
    {
        $this->assertFalse(Role::create('Coordinadora de Docencia')->isProtected());
    }

    public function test_protection_is_decided_by_the_exact_name(): void
    {
        // Near-misses must not accidentally inherit protection.
        $this->assertFalse(Role::reconstitute(1, 'superadmin')->isProtected());
        $this->assertFalse(Role::reconstitute(2, 'Administrator')->isProtected());
        $this->assertFalse(Role::reconstitute(3, 'Administradores')->isProtected());
    }

    public function test_a_structural_role_cannot_be_renamed(): void
    {
        $role = Role::reconstitute(1, 'Administrador');

        $this->expectException(RoleIsProtectedException::class);

        $role->rename('Administradora');
    }

    public function test_saving_a_structural_role_under_its_own_name_is_a_no_op(): void
    {
        $role = Role::reconstitute(1, 'Administrador');

        $role->rename('Administrador');

        $this->assertSame('Administrador', $role->name());
    }

    public function test_an_ordinary_role_can_be_renamed(): void
    {
        $role = Role::reconstitute(1, 'Docente');

        $role->rename('Docente titular');

        $this->assertSame('Docente titular', $role->name());
    }

    public function test_a_structural_role_may_still_have_its_permissions_changed(): void
    {
        $role = Role::reconstitute(1, 'Administrador', ['roles.view']);

        $role->syncPermissions(['roles.view', 'atinencia.verificar']);

        $this->assertTrue($role->hasPermission('atinencia.verificar'));
        $this->assertTrue($role->isProtected());
    }

    public function test_syncing_permissions_replaces_the_previous_set_instead_of_adding_to_it(): void
    {
        $role = Role::create('Docente', ['roles.view', 'roles.edit']);

        $role->syncPermissions(['atinencia.verificar']);

        $this->assertSame(['atinencia.verificar'], $role->permissions());
        $this->assertFalse($role->hasPermission('roles.view'));
    }

    public function test_permission_membership_is_an_exact_name_match(): void
    {
        $role = Role::create('Docente', ['roles.export_pdf']);

        $this->assertTrue($role->hasPermission('roles.export_pdf'));
        $this->assertFalse($role->hasPermission('roles.export'));
        $this->assertFalse($role->hasPermission('roles.export_excel'));
    }

    public function test_a_newly_created_role_has_no_persisted_identity_yet(): void
    {
        $this->assertNull(Role::create('Docente')->id());
        $this->assertSame(5, Role::reconstitute(5, 'Docente')->id());
    }
}
