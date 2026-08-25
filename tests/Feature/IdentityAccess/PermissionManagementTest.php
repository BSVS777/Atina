<?php

namespace Tests\Feature\IdentityAccess;

use App\Models\Permission;
use App\Models\Role;
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

    public function test_create_is_unavailable_when_the_catalog_is_complete(): void
    {
        $this->seed(PermissionSeeder::class);
        $this->actingAs($this->userWithPermissionManagementPermissions());

        $component = Livewire::test(PermissionComponent::class);

        $status = $component->instance()->catalogStatus();
        $this->assertTrue($status->isComplete());
        $this->assertSame(30, $status->registeredCount());
        $this->assertSame(30, $status->totalOfficialCount());
        $component->assertDontSee('$wire.openCreateModal()', false);

        // Defense in depth: a forged wire:click still refuses to open
        // the (now pointless) creation modal.
        $component->call('openCreateModal')
            ->assertSet('showModal', false)
            ->assertDispatched('toast', variant: 'danger');
    }

    public function test_create_becomes_available_when_a_permission_is_missing_and_completeness_is_restored_on_creation(): void
    {
        $this->seed(PermissionSeeder::class);
        Permission::query()->where(['module' => 'roles', 'action' => 'export_excel'])->delete();
        $this->actingAs($this->userWithPermissionManagementPermissions());

        $component = Livewire::test(PermissionComponent::class);

        $status = $component->instance()->catalogStatus();
        $this->assertFalse($status->isComplete());
        $this->assertSame(29, $status->registeredCount());
        // Only the module with a missing action is offered.
        $this->assertSame(['roles'], $status->availableModules());
        // Only the missing action is offered for that module.
        $this->assertSame(['export_excel'], $status->availableActionsFor('roles'));
        $component->assertSee('$wire.openCreateModal()', false);

        $component->call('openCreateModal')
            ->assertSet('showModal', true)
            ->set('form.module', 'roles')
            ->set('form.action', 'export_excel')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('permissions', ['module' => 'roles', 'action' => 'export_excel', 'name' => 'roles.export_excel']);

        $refreshed = Livewire::test(PermissionComponent::class);
        $this->assertTrue($refreshed->instance()->catalogStatus()->isComplete());
        $refreshed->assertDontSee('$wire.openCreateModal()', false);
    }

    public function test_a_forged_create_request_for_an_already_registered_permission_is_rejected(): void
    {
        $this->seed(PermissionSeeder::class);
        $this->actingAs($this->userWithPermissionManagementPermissions());

        // The Module/Action selects would never offer "roles"/"edit"
        // (already registered) — this simulates a forged request that
        // sets the Livewire property directly, bypassing the UI.
        Livewire::test(PermissionComponent::class)
            ->set('form.module', 'roles')
            ->set('form.action', 'edit')
            ->call('save')
            ->assertHasErrors(['form.action']);

        $this->assertDatabaseCount('permissions', 30);
    }

    public function test_a_duplicate_combination_forged_during_edit_returns_a_clear_permission_level_message(): void
    {
        $this->seed(PermissionSeeder::class);
        $this->actingAs($this->userWithPermissionManagementPermissions());

        // Module/Action are read-only in edit mode, so Rule::in() isn't
        // applied there — this exercises the unique-rule fallback alone
        // (see PermissionForm::rules()) by forging a collision with
        // another already-registered permission.
        $target = Permission::query()->where(['module' => 'roles', 'action' => 'delete'])->firstOrFail();

        Livewire::test(PermissionComponent::class)
            ->call('openEditModal', $target->id)
            ->set('form.module', 'roles')
            ->set('form.action', 'edit')
            ->call('save')
            ->assertHasErrors(['form.action' => 'unique'])
            ->assertSee(__('The permission :module.:action is already registered.', ['module' => 'roles', 'action' => 'edit']));

        $this->assertDatabaseHas('permissions', ['id' => $target->id, 'module' => 'roles', 'action' => 'delete']);
    }

    public function test_an_official_permission_cannot_be_deleted(): void
    {
        $this->seed(PermissionSeeder::class);
        $this->actingAs($this->userWithPermissionManagementPermissions());

        $target = Permission::query()->where(['module' => 'atinencia', 'action' => 'verificar'])->firstOrFail();

        Livewire::test(PermissionComponent::class)
            ->call('delete', $target->id)
            ->assertDispatched('toast', variant: 'danger');

        $this->assertDatabaseHas('permissions', ['id' => $target->id]);
    }

    public function test_deleting_a_protected_permission_does_not_remove_role_assignments(): void
    {
        $this->seed([PermissionSeeder::class, RoleSeeder::class]);
        $this->actingAs($this->userWithPermissionManagementPermissions());

        $role = Role::query()->where('name', 'Administrador')->firstOrFail();
        $target = Permission::query()->where(['module' => 'atinencia', 'action' => 'verificar'])->firstOrFail();
        $this->assertTrue($role->permissions()->whereKey($target->id)->exists());

        Livewire::test(PermissionComponent::class)->call('delete', $target->id);

        $this->assertTrue($role->permissions()->whereKey($target->id)->exists());
    }

    public function test_a_legacy_permission_outside_the_catalog_can_still_be_deleted(): void
    {
        $this->actingAs($this->userWithPermissionManagementPermissions());

        $legacy = Permission::query()->create([
            'module' => 'legacy_module',
            'action' => 'legacy_action',
            'name' => 'legacy_module.legacy_action',
        ]);

        Livewire::test(PermissionComponent::class)
            ->call('delete', $legacy->id)
            ->assertDispatched('toast', variant: 'success');

        $this->assertDatabaseMissing('permissions', ['id' => $legacy->id]);
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
