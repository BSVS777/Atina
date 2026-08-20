<?php

namespace Tests\Feature\Academic;

use App\Models\Permission;
use App\Models\Position;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Src\Academic\Teacher\Presentation\Livewire\TeacherComponent;
use Tests\TestCase;

class TeacherCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_with_permission_can_create_a_teacher(): void
    {
        $user = $this->userWithTeacherManagementPermissions();
        $position = Position::factory()->create();
        $this->actingAs($user);

        Livewire::test(TeacherComponent::class)
            ->set('form.positionId', $position->id)
            ->set('form.nationalId', '1-2345-6789')
            ->set('form.firstName', 'Ana')
            ->set('form.lastName', 'Rojas')
            ->set('form.secondLastName', 'Vega')
            ->set('form.estimatedWorkload', '0.50')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('docentes', [
            'cedula' => '1-2345-6789',
            'nombre' => 'Ana',
            'primer_apellido' => 'Rojas',
            'segundo_apellido' => 'Vega',
            'puesto_id' => $position->id,
        ]);
    }

    public function test_missing_required_fields_are_rejected(): void
    {
        $user = $this->userWithTeacherManagementPermissions();
        $this->actingAs($user);

        Livewire::test(TeacherComponent::class)
            ->call('save')
            ->assertHasErrors(['form.positionId', 'form.nationalId', 'form.firstName', 'form.lastName']);

        $this->assertDatabaseCount('docentes', 0);
    }

    public function test_a_duplicate_national_id_is_rejected(): void
    {
        $user = $this->userWithTeacherManagementPermissions();
        $position = Position::factory()->create();
        Teacher::factory()->create(['national_id' => '1-1111-1111']);
        $this->actingAs($user);

        Livewire::test(TeacherComponent::class)
            ->set('form.positionId', $position->id)
            ->set('form.nationalId', '1-1111-1111')
            ->set('form.firstName', 'Ana')
            ->set('form.lastName', 'Rojas')
            ->call('save')
            ->assertHasErrors(['form.nationalId' => 'unique']);

        $this->assertDatabaseCount('docentes', 1);
    }

    public function test_a_user_without_permission_cannot_open_the_create_modal(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(TeacherComponent::class)
            ->call('openCreateModal')
            ->assertForbidden();
    }

    public function test_a_user_without_permission_cannot_save_by_manipulating_component_state(): void
    {
        $user = User::factory()->create();
        $position = Position::factory()->create();
        $this->actingAs($user);

        Livewire::test(TeacherComponent::class)
            ->set('form.positionId', $position->id)
            ->set('form.nationalId', '1-9999-9999')
            ->set('form.firstName', 'Ana')
            ->set('form.lastName', 'Rojas')
            ->call('save')
            ->assertForbidden();

        $this->assertDatabaseCount('docentes', 0);
    }

    private function userWithTeacherManagementPermissions(): User
    {
        $user = User::factory()->create();

        $permission = Permission::query()->firstOrCreate(
            ['name' => 'usuarios.gestionar'],
            ['module' => 'usuarios', 'action' => 'gestionar'],
        );
        $user->givePermissionTo($permission->name);

        return $user;
    }
}
