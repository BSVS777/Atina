<?php

namespace Tests\Feature\Academic;

use App\Models\Permission;
use App\Models\Specialty;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Src\Academic\AcademicCredential\Domain\DegreeLevel;
use Src\Academic\Teacher\Presentation\Livewire\TeacherProfileComponent;
use Tests\TestCase;

/**
 * Specialty stays an id-backed catalog (also used by the affinity catalog)
 * even though the user types a plain name: typing a new name creates the
 * catalog row instead of leaving free text on the credential.
 */
class AcademicCredentialSpecialtyEntryTest extends TestCase
{
    use RefreshDatabase;

    public function test_typing_a_new_specialty_name_creates_it_and_links_the_credential(): void
    {
        $user = $this->userWithAcademicCredentialPermissions();
        $teacher = Teacher::factory()->create();
        $this->actingAs($user);

        Livewire::test(TeacherProfileComponent::class, ['teacher' => $teacher])
            ->set('form.specialtyName', 'Ingeniería en Robótica')
            ->set('form.degreeLevel', DegreeLevel::Licentiate->value)
            ->set('form.institution', 'UTN')
            ->set('form.startDate', '2015-03-01')
            ->set('form.endDate', '2020-11-30')
            ->call('save')
            ->assertHasNoErrors();

        $specialty = Specialty::query()->where('nombre', 'Ingeniería en Robótica')->firstOrFail();

        $this->assertDatabaseHas('atestados', [
            'docente_id' => $teacher->id,
            'especialidad_id' => $specialty->id,
        ]);
    }

    public function test_typing_an_existing_specialty_name_reuses_it_without_duplicating(): void
    {
        $user = $this->userWithAcademicCredentialPermissions();
        $teacher = Teacher::factory()->create();
        $specialty = Specialty::factory()->create(['name' => 'Ingeniería Industrial']);
        $this->actingAs($user);

        Livewire::test(TeacherProfileComponent::class, ['teacher' => $teacher])
            ->set('form.specialtyName', 'Ingeniería Industrial')
            ->set('form.degreeLevel', DegreeLevel::Licentiate->value)
            ->set('form.institution', 'UTN')
            ->set('form.startDate', '2015-03-01')
            ->set('form.endDate', '2020-11-30')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('especialidades', 1);
        $this->assertDatabaseHas('atestados', [
            'docente_id' => $teacher->id,
            'especialidad_id' => $specialty->id,
        ]);
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
