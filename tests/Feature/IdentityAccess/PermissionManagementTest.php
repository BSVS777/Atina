<?php

namespace Tests\Feature\IdentityAccess;

use App\Models\Permission;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Src\IdentityAccess\Permission\Presentation\Livewire\PermissionComponent;
use Tests\TestCase;

/**
 * Hardens the RBAC permission editor: Module/Action are now controlled
 * selects backed by PermissionCatalog, the technical name is always
 * server-derived, and an existing permission's identity is protected
 * from renaming — see Docs/DIARIO_DECISIONES_IA.md.
 */
class PermissionManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_valid_module_and_action_combination_can_be_created(): void
    {
        $this->actingAs($this->userWithPermissionManagementPermissions());

        Livewire::test(PermissionComponent::class)
            ->set('form.module', 'atinencia')
            ->set('form.action', 'verificar')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('permissions', [
            'module' => 'atinencia',
            'action' => 'verificar',
            'name' => 'atinencia.verificar',
        ]);
    }

    public function test_the_technical_name_is_derived_from_module_and_action_automatically(): void
    {
        $this->actingAs($this->userWithPermissionManagementPermissions());

        Livewire::test(PermissionComponent::class)
            ->set('form.module', 'oferta')
            ->set('form.action', 'consolidar')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('permissions', ['name' => 'oferta.consolidar']);
    }

    public function test_an_unofficial_module_is_rejected(): void
    {
        $this->actingAs($this->userWithPermissionManagementPermissions());

        Livewire::test(PermissionComponent::class)
            ->set('form.module', 'atinencias')
            ->set('form.action', 'verificar')
            ->call('save')
            ->assertHasErrors(['form.module']);

        $this->assertDatabaseMissing('permissions', ['module' => 'atinencias']);
    }

    public function test_an_action_not_belonging_to_the_selected_module_is_rejected(): void
    {
        $this->actingAs($this->userWithPermissionManagementPermissions());

        // "aprobar" is only official under nota_tecnica, not roles.
        Livewire::test(PermissionComponent::class)
            ->set('form.module', 'roles')
            ->set('form.action', 'aprobar')
            ->call('save')
            ->assertHasErrors(['form.action']);

        $this->assertDatabaseMissing('permissions', ['module' => 'roles', 'action' => 'aprobar']);
    }

    public function test_a_duplicate_permission_is_rejected(): void
    {
        Permission::query()->create(['module' => 'roles', 'action' => 'edit', 'name' => 'roles.edit']);
        $this->actingAs($this->userWithPermissionManagementPermissions());

        Livewire::test(PermissionComponent::class)
            ->set('form.module', 'roles')
            ->set('form.action', 'edit')
            ->call('save')
            ->assertHasErrors(['form.action' => 'unique']);

        $this->assertDatabaseCount('permissions', 6);
    }

    public function test_changing_module_resets_an_action_that_is_no_longer_valid(): void
    {
        $this->actingAs($this->userWithPermissionManagementPermissions());

        Livewire::test(PermissionComponent::class)
            ->set('form.module', 'roles')
            ->set('form.action', 'edit')
            ->assertSet('form.action', 'edit')
            ->set('form.module', 'atinencia')
            ->assertSet('form.action', '');
    }

    public function test_changing_module_keeps_an_action_still_valid_for_the_new_module(): void
    {
        $this->actingAs($this->userWithPermissionManagementPermissions());

        // "gestionar" is a valid action under both atestados and
        // catalogo, so switching between them must not clear it.
        Livewire::test(PermissionComponent::class)
            ->set('form.module', 'atestados')
            ->set('form.action', 'gestionar')
            ->assertSet('form.action', 'gestionar')
            ->set('form.module', 'catalogo')
            ->assertSet('form.action', 'gestionar');
    }

    public function test_an_existing_permission_renders_its_module_and_action_correctly_in_edit_mode(): void
    {
        $permission = Permission::query()->create([
            'module' => 'atinencia',
            'action' => 'verificar',
            'name' => 'atinencia.verificar',
        ]);
        $this->actingAs($this->userWithPermissionManagementPermissions());

        Livewire::test(PermissionComponent::class)
            ->call('openEditModal', $permission->id)
            ->assertSet('form.module', 'atinencia')
            ->assertSet('form.action', 'verificar');
    }

    public function test_an_official_permission_cannot_be_renamed_by_forging_component_state(): void
    {
        $permission = Permission::query()->create([
            'module' => 'atinencia',
            'action' => 'verificar',
            'name' => 'atinencia.verificar',
        ]);
        $this->actingAs($this->userWithPermissionManagementPermissions());

        // The UI disables these selects in edit mode, but a forged
        // Livewire request could still set them directly — the Domain
        // guard (Permission::redefine()) must be what actually stops it.
        Livewire::test(PermissionComponent::class)
            ->call('openEditModal', $permission->id)
            ->set('form.module', 'roles')
            ->set('form.action', 'edit')
            ->call('save')
            ->assertDispatched('toast', variant: 'danger');

        $this->assertDatabaseHas('permissions', [
            'id' => $permission->id,
            'module' => 'atinencia',
            'action' => 'verificar',
        ]);
        $this->assertDatabaseMissing('permissions', ['module' => 'roles', 'action' => 'edit']);
    }

    public function test_permission_catalog_seeding_is_unaffected_by_the_refactor(): void
    {
        $this->seed([PermissionSeeder::class, RoleSeeder::class]);

        // 2 manageable modules x 7 actions + 16 institutional permissions.
        $this->assertDatabaseCount('permissions', 30);

        $admin = User::factory()->create();
        $admin->assignRole('Administrador');

        foreach (['atinencia.verificar', 'catalogo.gestionar', 'nota_tecnica.aprobar'] as $officialPermission) {
            $this->assertTrue($admin->hasPermissionTo($officialPermission));
        }
    }

    private function userWithPermissionManagementPermissions(): User
    {
        $user = User::factory()->create();

        foreach (['view', 'search', 'create', 'edit', 'delete'] as $action) {
            $permission = Permission::query()->firstOrCreate(
                ['name' => "permissions.{$action}"],
                ['module' => 'permissions', 'action' => $action],
            );
            $user->givePermissionTo($permission->name);
        }

        return $user;
    }
}
