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
use Livewire\Livewire;
use Src\Academic\AffinityCatalog\Application\DTOs\AffinityCatalogVersionDTO;
use Src\Academic\AffinityCatalog\Application\UseCases\CreateAffinityCatalogVersionUseCase;
use Src\Academic\TeacherAssignment\Application\DTOs\AttachTechnicalNoteDTO;
use Src\Academic\TeacherAssignment\Application\DTOs\ProposeTeacherAssignmentDTO;
use Src\Academic\TeacherAssignment\Application\UseCases\AttachTechnicalNoteUseCase;
use Src\Academic\TeacherAssignment\Application\UseCases\ProposeTeacherAssignmentUseCase;
use Src\Academic\TeacherAssignment\Domain\Contracts\UploadedDocument;
use Src\Academic\TeacherAssignment\Presentation\Livewire\TeacherAssignmentComponent;
use Tests\TestCase;

/**
 * DO-02a/DO-02b/DO-02d acceptance closeout: the explicit blocking message
 * for a blocked "No Atinente" result, the "pending manual approval" label
 * for an undecided "Sin catálogo" result, and the University Council
 * ratification label + deadline for a pending Technical Note.
 */
class TeacherAssignmentUiAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    private function userWithPermission(string $name): User
    {
        $user = User::factory()->create();
        [$module, $action] = explode('.', $name, 2);

        $permission = Permission::query()->firstOrCreate(['name' => $name], ['module' => $module, 'action' => $action]);
        $user->givePermissionTo($permission->name);

        return $user;
    }

    public function test_a_not_matched_result_shows_the_explicit_blocking_message(): void
    {
        $this->actingAs($this->userWithPermission('atinencia.verificar'));

        $catalogSpecialty = Specialty::factory()->create();
        $teacherSpecialty = Specialty::factory()->create();
        $course = Course::factory()->create();
        $term = AcademicTerm::factory()->create(['start_date' => '2026-05-01']);
        $group = CourseGroup::factory()->create(['course_id' => $course->id, 'academic_term_id' => $term->id]);

        app(CreateAffinityCatalogVersionUseCase::class)->handle(new AffinityCatalogVersionDTO(
            courseId: $course->id,
            councilAgreement: 'Acuerdo 1-2026',
            gazetteNumber: '10',
            effectiveStartDate: '2026-01-01',
            effectiveEndDate: null,
            specialtyIds: [$catalogSpecialty->id],
        ), null);

        $teacher = Teacher::factory()->create();
        AcademicCredential::factory()->create(['docente_id' => $teacher->id, 'especialidad_id' => $teacherSpecialty->id]);

        app(ProposeTeacherAssignmentUseCase::class)->handle(new ProposeTeacherAssignmentDTO(
            courseGroupId: $group->id,
            teacherId: $teacher->id,
            courseId: $course->id,
            targetDate: '2026-05-01',
        ), null);

        Livewire::test(TeacherAssignmentComponent::class)
            ->assertSee(__('No Atinente'))
            ->assertSee(__('Assignment blocked: the teacher does not meet the affinity required for this course.'));
    }

    public function test_an_undecided_no_catalog_result_shows_the_pending_manual_approval_label(): void
    {
        $this->actingAs($this->userWithPermission('atinencia.verificar'));

        $course = Course::factory()->create();
        $term = AcademicTerm::factory()->create(['start_date' => '2026-05-01']);
        $group = CourseGroup::factory()->create(['course_id' => $course->id, 'academic_term_id' => $term->id]);
        $teacher = Teacher::factory()->create();

        app(ProposeTeacherAssignmentUseCase::class)->handle(new ProposeTeacherAssignmentDTO(
            courseGroupId: $group->id,
            teacherId: $teacher->id,
            courseId: $course->id,
            targetDate: '2026-05-01',
        ), null);

        Livewire::test(TeacherAssignmentComponent::class)
            ->assertSee(__('Sin catálogo'))
            ->assertSee(__('No catalog — pending manual approval'));
    }

    public function test_a_pending_technical_note_shows_the_council_ratification_label_and_deadline(): void
    {
        $this->actingAs($this->userWithPermission('atinencia.verificar'));

        $catalogSpecialty = Specialty::factory()->create();
        $teacherSpecialty = Specialty::factory()->create();
        $course = Course::factory()->create();
        $term = AcademicTerm::factory()->create(['start_date' => '2026-05-01']);
        $group = CourseGroup::factory()->create(['course_id' => $course->id, 'academic_term_id' => $term->id]);

        app(CreateAffinityCatalogVersionUseCase::class)->handle(new AffinityCatalogVersionDTO(
            courseId: $course->id,
            councilAgreement: 'Acuerdo 1-2026',
            gazetteNumber: '10',
            effectiveStartDate: '2026-01-01',
            effectiveEndDate: null,
            specialtyIds: [$catalogSpecialty->id],
        ), null);

        $teacher = Teacher::factory()->create();
        AcademicCredential::factory()->create(['docente_id' => $teacher->id, 'especialidad_id' => $teacherSpecialty->id]);

        $result = app(ProposeTeacherAssignmentUseCase::class)->handle(new ProposeTeacherAssignmentDTO(
            courseGroupId: $group->id,
            teacherId: $teacher->id,
            courseId: $course->id,
            targetDate: '2026-05-01',
        ), null);

        $deadline = now()->addDays(30)->toDateString();

        app(AttachTechnicalNoteUseCase::class)->handle(new AttachTechnicalNoteDTO(
            teacherAssignmentId: $result->assignment->id(),
            ratificationDeadline: $deadline,
            document: new UploadedDocument(
                storagePath: 'technical-notes/fake.pdf',
                originalFileName: 'fake.pdf',
                mimeType: 'application/pdf',
                sizeBytes: 100,
                hashSha256: hash('sha256', 'fake'),
            ),
        ), null);

        Livewire::test(TeacherAssignmentComponent::class)
            ->assertSee(__('Technical note — ratification pending from the University Council'))
            ->assertSee(__('Deadline: :date', ['date' => $deadline]));
    }
}
