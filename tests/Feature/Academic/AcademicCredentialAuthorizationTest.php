<?php

namespace Tests\Feature\Academic;

use App\Models\AcademicCredential;
use App\Models\Permission;
use App\Models\Specialty;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Src\Academic\AcademicCredential\Domain\DegreeLevel;
use Src\Academic\Teacher\Presentation\Livewire\TeacherProfileComponent;
use Tests\TestCase;

class AcademicCredentialAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_without_permission_cannot_open_the_create_modal(): void
    {
        $user = User::factory()->create();
        $teacher = Teacher::factory()->create();
        $this->actingAs($user);

        Livewire::test(TeacherProfileComponent::class, ['teacher' => $teacher])
            ->call('openCreateModal')
            ->assertForbidden();
    }

    public function test_a_user_without_permission_cannot_save_by_manipulating_component_state(): void
    {
        $user = User::factory()->create();
        $teacher = Teacher::factory()->create();
        $specialty = Specialty::factory()->create();
        $this->actingAs($user);

        Livewire::test(TeacherProfileComponent::class, ['teacher' => $teacher])
            ->set('form.specialtyId', $specialty->id)
            ->set('form.degreeLevel', DegreeLevel::Licentiate->value)
            ->set('form.institution', 'UTN')
            ->set('form.yearObtained', 2020)
            ->call('save')
            ->assertForbidden();

        $this->assertDatabaseCount('atestados', 0);
    }

    public function test_a_user_with_permission_can_create_a_credential(): void
    {
        $user = $this->userWithAcademicCredentialPermissions();
        $teacher = Teacher::factory()->create();
        $specialty = Specialty::factory()->create();
        $this->actingAs($user);

        Livewire::test(TeacherProfileComponent::class, ['teacher' => $teacher])
            ->set('form.specialtyId', $specialty->id)
            ->set('form.degreeLevel', DegreeLevel::Licentiate->value)
            ->set('form.institution', 'UTN')
            ->set('form.yearObtained', 2020)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('atestados', [
            'docente_id' => $teacher->id,
            'especialidad_id' => $specialty->id,
            'institucion' => 'UTN',
        ]);
    }

    public function test_a_user_without_edit_permission_cannot_update_an_existing_credential(): void
    {
        $user = User::factory()->create();
        $teacher = Teacher::factory()->create();
        $credential = AcademicCredential::factory()->create(['docente_id' => $teacher->id]);
        $this->actingAs($user);

        Livewire::test(TeacherProfileComponent::class, ['teacher' => $teacher])
            ->call('openEditModal', $credential->id)
            ->assertForbidden();
    }

    private function userWithAcademicCredentialPermissions(): User
    {
        $user = User::factory()->create();

        $permission = Permission::query()->firstOrCreate(
            ['name' => 'atestados.gestionar'],
            ['module' => 'atestados', 'action' => 'gestionar'],
        );
        $user->givePermissionTo($permission->name);

        return $user;
    }
}
