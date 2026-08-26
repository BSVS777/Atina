<?php

namespace Tests\Feature\Academic;

use App\Models\AcademicCredential;
use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Specialty;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Src\Academic\AcademicCredential\Domain\DegreeLevel;
use Src\Academic\Teacher\Presentation\Livewire\TeacherProfileComponent;
use Tests\TestCase;

class AcademicCredentialAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_credential_records_an_audit_entry(): void
    {
        $user = $this->userWithAcademicCredentialPermissions();
        $teacher = Teacher::factory()->create();
        $specialty = Specialty::factory()->create();
        $this->actingAs($user);

        Livewire::test(TeacherProfileComponent::class, ['teacher' => $teacher])
            ->set('form.specialtyName', $specialty->name)
            ->set('form.degreeLevel', DegreeLevel::Master->value)
            ->set('form.institution', 'UCR')
            ->set('form.startDate', '2014-03-01')
            ->set('form.endDate', '2019-11-30')
            ->call('save')
            ->assertHasNoErrors();

        $credential = AcademicCredential::where('docente_id', $teacher->id)->firstOrFail();

        $this->assertDatabaseHas('auditorias', [
            'user_id' => $user->id,
            'auditable_type' => AcademicCredential::class,
            'auditable_id' => $credential->id,
            'accion' => 'Creación',
        ]);

        $log = AuditLog::where('auditable_id', $credential->id)->firstOrFail();
        // assertEquals (not assertSame): MySQL's binary JSON storage doesn't
        // guarantee original key order, so comparisons must ignore order.
        $this->assertEquals(['before' => null, 'after' => 'UCR'], $log->cambios['institution']);
    }

    public function test_editing_only_audits_the_fields_that_changed(): void
    {
        $user = $this->userWithAcademicCredentialPermissions();
        $teacher = Teacher::factory()->create();
        $specialty = Specialty::factory()->create();
        $credential = AcademicCredential::factory()->create([
            'docente_id' => $teacher->id,
            'especialidad_id' => $specialty->id,
            'institucion' => 'UTN',
            'fecha_inicio' => '2010-03-01',
            'fecha_fin' => '2015-11-30',
        ]);
        $this->actingAs($user);

        Livewire::test(TeacherProfileComponent::class, ['teacher' => $teacher])
            ->call('openEditModal', $credential->id)
            ->set('form.institution', 'UNA')
            ->call('save')
            ->assertHasNoErrors();

        $editEntry = AuditLog::where('auditable_id', $credential->id)
            ->where('accion', 'Modificación')
            ->firstOrFail();

        $this->assertEquals([
            'institution' => ['before' => 'UTN', 'after' => 'UNA'],
        ], $editEntry->cambios);
    }

    public function test_a_rejected_attempt_does_not_create_an_audit_entry(): void
    {
        $user = User::factory()->create();
        $teacher = Teacher::factory()->create();
        $specialty = Specialty::factory()->create();
        $this->actingAs($user);

        Livewire::test(TeacherProfileComponent::class, ['teacher' => $teacher])
            ->set('form.specialtyName', $specialty->name)
            ->set('form.degreeLevel', DegreeLevel::Licentiate->value)
            ->set('form.institution', 'UTN')
            ->set('form.startDate', '2015-03-01')
            ->set('form.endDate', '2020-11-30')
            ->call('save')
            ->assertForbidden();

        $this->assertDatabaseCount('auditorias', 0);
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
