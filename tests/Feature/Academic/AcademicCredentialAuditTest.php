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
            ->set('form.specialtyId', $specialty->id)
            ->set('form.degreeLevel', DegreeLevel::Master->value)
            ->set('form.institution', 'UCR')
            ->set('form.yearObtained', 2019)
            ->call('save')
            ->assertHasNoErrors();

        $credential = AcademicCredential::where('teacher_id', $teacher->id)->firstOrFail();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'auditable_type' => AcademicCredential::class,
            'auditable_id' => $credential->id,
            'action' => 'created',
        ]);

        $log = AuditLog::where('auditable_id', $credential->id)->firstOrFail();
        $this->assertNotNull($log->created_at);
        // assertEquals (not assertSame): MySQL's binary JSON storage doesn't
        // guarantee original key order, so comparisons must ignore order.
        $this->assertEquals(['before' => null, 'after' => 'UCR'], $log->changes['institution']);
    }

    public function test_editing_only_audits_the_fields_that_changed(): void
    {
        $user = $this->userWithAcademicCredentialPermissions();
        $teacher = Teacher::factory()->create();
        $specialty = Specialty::factory()->create();
        $credential = AcademicCredential::factory()->create([
            'teacher_id' => $teacher->id,
            'specialty_id' => $specialty->id,
            'institution' => 'UTN',
            'year_obtained' => 2015,
        ]);
        $this->actingAs($user);

        Livewire::test(TeacherProfileComponent::class, ['teacher' => $teacher])
            ->call('openEditModal', $credential->id)
            ->set('form.institution', 'UNA')
            ->call('save')
            ->assertHasNoErrors();

        $editEntry = AuditLog::where('auditable_id', $credential->id)
            ->where('action', 'updated')
            ->firstOrFail();

        $this->assertEquals([
            'institution' => ['before' => 'UTN', 'after' => 'UNA'],
        ], $editEntry->changes);
    }

    public function test_a_rejected_attempt_does_not_create_an_audit_entry(): void
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

        $this->assertDatabaseCount('audit_logs', 0);
    }

    private function userWithAcademicCredentialPermissions(): User
    {
        $user = User::factory()->create();

        foreach (['create', 'edit'] as $action) {
            $permission = Permission::query()->firstOrCreate(
                ['name' => "academic_credentials.{$action}"],
                ['module' => 'academic_credentials', 'action' => $action],
            );
            $user->givePermissionTo($permission->name);
        }

        return $user;
    }
}
