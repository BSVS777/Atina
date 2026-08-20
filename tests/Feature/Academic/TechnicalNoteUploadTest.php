<?php

namespace Tests\Feature\Academic;

use App\Models\AcademicCredential;
use App\Models\AcademicTerm;
use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\Permission;
use App\Models\Specialty;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Src\Academic\AffinityCatalog\Application\DTOs\AffinityCatalogVersionDTO;
use Src\Academic\AffinityCatalog\Application\UseCases\CreateAffinityCatalogVersionUseCase;
use Src\Academic\TeacherAssignment\Application\DTOs\ProposeTeacherAssignmentDTO;
use Src\Academic\TeacherAssignment\Application\UseCases\ProposeTeacherAssignmentUseCase;
use Src\Academic\TeacherAssignment\Presentation\Livewire\TeacherAssignmentComponent;
use Tests\TestCase;

/**
 * Exercises the actual Livewire upload path (component + Form object +
 * TemporaryUploadedFile) rather than injecting an UploadedDocument DTO
 * directly, since that's the path the previously-reported upload bug
 * (an infinite Alpine event-dispatch loop in the dropzone) lived in and
 * had no coverage.
 */
class TechnicalNoteUploadTest extends TestCase
{
    use RefreshDatabase;

    private function notMatchedAssignmentId(): int
    {
        $catalogSpecialty = Specialty::factory()->create();
        $teacherSpecialty = Specialty::factory()->create();
        $course = Course::factory()->create();
        $term = AcademicTerm::factory()->create(['start_date' => '2026-05-01']);
        $group = CourseGroup::factory()->create(['course_id' => $course->id, 'academic_term_id' => $term->id]);

        app(CreateAffinityCatalogVersionUseCase::class)->handle(
            new AffinityCatalogVersionDTO(
                courseId: $course->id,
                councilAgreement: 'Acuerdo 1-2026',
                gazetteNumber: '10',
                effectiveStartDate: '2026-01-01',
                effectiveEndDate: null,
                specialtyIds: [$catalogSpecialty->id],
            ),
            null,
        );

        $teacher = Teacher::factory()->create();
        AcademicCredential::factory()->create(['docente_id' => $teacher->id, 'especialidad_id' => $teacherSpecialty->id]);

        $result = app(ProposeTeacherAssignmentUseCase::class)->handle(new ProposeTeacherAssignmentDTO(
            courseGroupId: $group->id,
            teacherId: $teacher->id,
            courseId: $course->id,
            targetDate: '2026-05-01',
        ), null);

        return $result->assignment->id();
    }

    private function userWithPermission(string $name): User
    {
        $user = User::factory()->create();
        [$module, $action] = explode('.', $name, 2);

        $permission = Permission::query()->firstOrCreate(['name' => $name], ['module' => $module, 'action' => $action]);
        $user->givePermissionTo($permission->name);

        return $user;
    }

    public function test_authorized_user_can_upload_a_valid_pdf_technical_note(): void
    {
        $this->actingAs($this->userWithPermission('atinencia.verificar'));
        $assignmentId = $this->notMatchedAssignmentId();

        Livewire::test(TeacherAssignmentComponent::class)
            ->call('openNoteModal', $assignmentId)
            ->set('noteForm.document', UploadedFile::fake()->create('criterio.pdf', 500, 'application/pdf'))
            ->set('noteForm.ratificationDeadline', now()->addDays(30)->toDateString())
            ->call('attachTechnicalNote')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('notas_tecnicas', 1);
        $this->assertDatabaseHas('notas_tecnicas', ['estado' => 'Ratificación pendiente']);
    }

    public function test_missing_document_is_rejected(): void
    {
        $this->actingAs($this->userWithPermission('atinencia.verificar'));
        $assignmentId = $this->notMatchedAssignmentId();

        Livewire::test(TeacherAssignmentComponent::class)
            ->call('openNoteModal', $assignmentId)
            ->set('noteForm.ratificationDeadline', now()->addDays(30)->toDateString())
            ->call('attachTechnicalNote')
            ->assertHasErrors(['noteForm.document']);

        $this->assertDatabaseCount('notas_tecnicas', 0);
    }

    public function test_non_pdf_file_is_rejected(): void
    {
        $this->actingAs($this->userWithPermission('atinencia.verificar'));
        $assignmentId = $this->notMatchedAssignmentId();

        Livewire::test(TeacherAssignmentComponent::class)
            ->call('openNoteModal', $assignmentId)
            ->set('noteForm.document', UploadedFile::fake()->create('criterio.txt', 100, 'text/plain'))
            ->set('noteForm.ratificationDeadline', now()->addDays(30)->toDateString())
            ->call('attachTechnicalNote')
            ->assertHasErrors(['noteForm.document']);

        $this->assertDatabaseCount('notas_tecnicas', 0);
    }

    public function test_oversized_pdf_is_rejected(): void
    {
        $this->actingAs($this->userWithPermission('atinencia.verificar'));
        $assignmentId = $this->notMatchedAssignmentId();

        Livewire::test(TeacherAssignmentComponent::class)
            ->call('openNoteModal', $assignmentId)
            ->set('noteForm.document', UploadedFile::fake()->create('criterio.pdf', 10241, 'application/pdf'))
            ->set('noteForm.ratificationDeadline', now()->addDays(30)->toDateString())
            ->call('attachTechnicalNote')
            ->assertHasErrors(['noteForm.document']);

        $this->assertDatabaseCount('notas_tecnicas', 0);
    }

    public function test_invalid_deadline_is_rejected_and_creates_no_partial_records(): void
    {
        $this->actingAs($this->userWithPermission('atinencia.verificar'));
        $assignmentId = $this->notMatchedAssignmentId();

        Livewire::test(TeacherAssignmentComponent::class)
            ->call('openNoteModal', $assignmentId)
            ->set('noteForm.document', UploadedFile::fake()->create('criterio.pdf', 500, 'application/pdf'))
            ->set('noteForm.ratificationDeadline', now()->subDay()->toDateString())
            ->call('attachTechnicalNote')
            ->assertHasErrors(['noteForm.ratificationDeadline']);

        $this->assertDatabaseCount('notas_tecnicas', 0);
    }
}
