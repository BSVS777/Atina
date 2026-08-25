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
use Src\Academic\TeacherAssignment\Application\UseCases\DecideNoCatalogAssignmentUseCase;
use Src\Academic\TeacherAssignment\Application\UseCases\ProposeTeacherAssignmentUseCase;
use Src\Academic\TeacherAssignment\Domain\Contracts\UploadedDocument;
use Src\Academic\TeacherAssignment\Presentation\Livewire\TeacherAssignmentComponent;
use Tests\TestCase;

/**
 * Corrective UX: an authorized user can fix a misclicked teacher/group
 * (Edit, reruns the real affinity verification) or remove an accidental
 * proposal (Delete) from the Verificación de Atinencias screen — but
 * never once formal history (a Technical Note or a manual "Sin
 * catálogo" decision) depends on the record.
 */
class TeacherAssignmentEditDeleteTest extends TestCase
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

    /**
     * @return array{assignmentId: int, group: CourseGroup, course: Course, wrongTeacher: Teacher, correctTeacher: Teacher}
     */
    private function proposeNotMatchedWithACorrectAlternative(): array
    {
        $catalogSpecialty = Specialty::factory()->create();
        $wrongSpecialty = Specialty::factory()->create();
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

        $wrongTeacher = Teacher::factory()->create();
        AcademicCredential::factory()->create(['docente_id' => $wrongTeacher->id, 'especialidad_id' => $wrongSpecialty->id]);

        $correctTeacher = Teacher::factory()->create();
        AcademicCredential::factory()->create(['docente_id' => $correctTeacher->id, 'especialidad_id' => $catalogSpecialty->id]);

        $result = app(ProposeTeacherAssignmentUseCase::class)->handle(new ProposeTeacherAssignmentDTO(
            courseGroupId: $group->id,
            teacherId: $wrongTeacher->id,
            courseId: $course->id,
            targetDate: '2026-05-01',
        ), null);

        return [
            'assignmentId' => $result->assignment->id(),
            'group' => $group,
            'course' => $course,
            'wrongTeacher' => $wrongTeacher,
            'correctTeacher' => $correctTeacher,
        ];
    }

    private function proposeWithoutCatalog(): int
    {
        $course = Course::factory()->create();
        $term = AcademicTerm::factory()->create(['start_date' => '2026-05-01']);
        $group = CourseGroup::factory()->create(['course_id' => $course->id, 'academic_term_id' => $term->id]);
        $teacher = Teacher::factory()->create();

        $result = app(ProposeTeacherAssignmentUseCase::class)->handle(new ProposeTeacherAssignmentDTO(
            courseGroupId: $group->id,
            teacherId: $teacher->id,
            courseId: $course->id,
            targetDate: '2026-05-01',
        ), null);

        return $result->assignment->id();
    }

    public function test_authorized_user_can_edit_an_accidental_proposal_and_it_reruns_the_real_affinity_verification(): void
    {
        $fixture = $this->proposeNotMatchedWithACorrectAlternative();
        $this->actingAs($this->userWithPermission('atinencia.verificar'));

        Livewire::test(TeacherAssignmentComponent::class)
            ->call('openEditModal', $fixture['assignmentId'], $fixture['wrongTeacher']->id, $fixture['group']->id)
            ->set('proposeForm.teacherId', $fixture['correctTeacher']->id)
            ->call('propose')
            ->assertHasNoErrors();

        // The result was computed by rerunning the matching algorithm
        // against the corrected teacher — never set by hand.
        $this->assertDatabaseHas('asignaciones_docentes', [
            'id' => $fixture['assignmentId'],
            'docente_id' => $fixture['correctTeacher']->id,
            'estado' => 'Confirmada',
        ]);

        // D11/D12: the original "No Atinente" event is preserved, not
        // overwritten — a new event is appended instead.
        $this->assertDatabaseCount('verificaciones_atinencia', 2);
        $this->assertDatabaseHas('verificaciones_atinencia', [
            'asignacion_docente_id' => $fixture['assignmentId'],
            'resultado' => 'No Atinente',
        ]);
        $this->assertDatabaseHas('verificaciones_atinencia', [
            'asignacion_docente_id' => $fixture['assignmentId'],
            'resultado' => 'Atinente',
        ]);

        $this->assertDatabaseHas('auditorias', [
            'auditable_type' => 'teacher_assignment',
            'auditable_id' => $fixture['assignmentId'],
            'accion' => 'Modificación',
        ]);
    }

    public function test_a_user_without_permission_cannot_open_the_edit_modal(): void
    {
        $fixture = $this->proposeNotMatchedWithACorrectAlternative();
        $this->actingAs(User::factory()->create());

        Livewire::test(TeacherAssignmentComponent::class)
            ->call('openEditModal', $fixture['assignmentId'], $fixture['wrongTeacher']->id, $fixture['group']->id)
            ->assertForbidden();
    }

    public function test_editing_is_blocked_once_a_technical_note_exists_for_the_assignment(): void
    {
        $fixture = $this->proposeNotMatchedWithACorrectAlternative();
        $this->actingAs($this->userWithPermission('atinencia.verificar'));

        app(AttachTechnicalNoteUseCase::class)->handle(new AttachTechnicalNoteDTO(
            teacherAssignmentId: $fixture['assignmentId'],
            ratificationDeadline: now()->addDays(30)->toDateString(),
            document: new UploadedDocument(
                storagePath: 'technical-notes/fake.pdf',
                originalFileName: 'fake.pdf',
                mimeType: 'application/pdf',
                sizeBytes: 100,
                hashSha256: hash('sha256', 'fake'),
            ),
        ), null);

        Livewire::test(TeacherAssignmentComponent::class)
            ->call('openEditModal', $fixture['assignmentId'], $fixture['wrongTeacher']->id, $fixture['group']->id)
            ->set('proposeForm.teacherId', $fixture['correctTeacher']->id)
            ->call('propose')
            ->assertHasErrors(['proposeForm.courseGroupId']);

        $this->assertDatabaseHas('asignaciones_docentes', [
            'id' => $fixture['assignmentId'],
            'docente_id' => $fixture['wrongTeacher']->id,
        ]);
    }

    public function test_authorized_user_can_delete_a_simple_accidental_record(): void
    {
        $assignmentId = $this->proposeWithoutCatalog();
        $this->actingAs($this->userWithPermission('atinencia.verificar'));

        Livewire::test(TeacherAssignmentComponent::class)
            ->call('delete', $assignmentId);

        $this->assertDatabaseMissing('asignaciones_docentes', ['id' => $assignmentId]);
        $this->assertDatabaseMissing('verificaciones_atinencia', ['asignacion_docente_id' => $assignmentId]);
        $this->assertDatabaseHas('auditorias', [
            'auditable_type' => 'teacher_assignment',
            'auditable_id' => $assignmentId,
            'accion' => 'Eliminación',
        ]);
    }

    public function test_deleting_an_automatically_confirmed_matched_proposal_is_allowed(): void
    {
        // A "Matched" auto-confirmation is business-rule output, not a
        // formal human decision — still a correctable, plain proposal.
        $fixture = $this->proposeNotMatchedWithACorrectAlternative();
        $this->actingAs($this->userWithPermission('atinencia.verificar'));

        Livewire::test(TeacherAssignmentComponent::class)
            ->call('openEditModal', $fixture['assignmentId'], $fixture['wrongTeacher']->id, $fixture['group']->id)
            ->set('proposeForm.teacherId', $fixture['correctTeacher']->id)
            ->call('propose')
            ->assertHasNoErrors();

        Livewire::test(TeacherAssignmentComponent::class)
            ->call('delete', $fixture['assignmentId']);

        $this->assertDatabaseMissing('asignaciones_docentes', ['id' => $fixture['assignmentId']]);
    }

    public function test_a_user_without_permission_cannot_delete_an_assignment(): void
    {
        $assignmentId = $this->proposeWithoutCatalog();
        $this->actingAs(User::factory()->create());

        Livewire::test(TeacherAssignmentComponent::class)
            ->call('delete', $assignmentId)
            ->assertForbidden();

        $this->assertDatabaseHas('asignaciones_docentes', ['id' => $assignmentId]);
    }

    public function test_deletion_is_blocked_once_a_technical_note_exists_for_the_assignment(): void
    {
        $fixture = $this->proposeNotMatchedWithACorrectAlternative();
        $this->actingAs($this->userWithPermission('atinencia.verificar'));

        app(AttachTechnicalNoteUseCase::class)->handle(new AttachTechnicalNoteDTO(
            teacherAssignmentId: $fixture['assignmentId'],
            ratificationDeadline: now()->addDays(30)->toDateString(),
            document: new UploadedDocument(
                storagePath: 'technical-notes/fake.pdf',
                originalFileName: 'fake.pdf',
                mimeType: 'application/pdf',
                sizeBytes: 100,
                hashSha256: hash('sha256', 'fake'),
            ),
        ), null);

        Livewire::test(TeacherAssignmentComponent::class)
            ->call('delete', $fixture['assignmentId'])
            ->assertDispatched('toast', variant: 'danger');

        $this->assertDatabaseHas('asignaciones_docentes', ['id' => $fixture['assignmentId']]);
        $this->assertDatabaseHas('notas_tecnicas', ['asignacion_docente_id' => $fixture['assignmentId']]);
    }

    public function test_deletion_is_blocked_once_a_manual_no_catalog_decision_was_made(): void
    {
        $assignmentId = $this->proposeWithoutCatalog();
        app(DecideNoCatalogAssignmentUseCase::class)->handle($assignmentId, approve: true, actorUserId: null);

        $this->actingAs($this->userWithPermission('atinencia.verificar'));

        Livewire::test(TeacherAssignmentComponent::class)
            ->call('delete', $assignmentId)
            ->assertDispatched('toast', variant: 'danger');

        $this->assertDatabaseHas('asignaciones_docentes', ['id' => $assignmentId, 'estado' => 'Confirmada']);
    }

    public function test_deletion_of_an_undecided_no_catalog_proposal_is_still_allowed(): void
    {
        // No manual decision has been made yet, so this is still a plain
        // accidental "Sin catálogo" proposal.
        $assignmentId = $this->proposeWithoutCatalog();
        $this->actingAs($this->userWithPermission('atinencia.verificar'));

        Livewire::test(TeacherAssignmentComponent::class)
            ->call('delete', $assignmentId);

        $this->assertDatabaseMissing('asignaciones_docentes', ['id' => $assignmentId]);
    }

    public function test_existing_technical_note_ratification_flow_still_works_after_the_edit_delete_change(): void
    {
        $fixture = $this->proposeNotMatchedWithACorrectAlternative();

        app(AttachTechnicalNoteUseCase::class)->handle(new AttachTechnicalNoteDTO(
            teacherAssignmentId: $fixture['assignmentId'],
            ratificationDeadline: now()->addDays(30)->toDateString(),
            document: new UploadedDocument(
                storagePath: 'technical-notes/fake.pdf',
                originalFileName: 'fake.pdf',
                mimeType: 'application/pdf',
                sizeBytes: 100,
                hashSha256: hash('sha256', 'fake'),
            ),
        ), null);

        $this->actingAs($this->userWithPermission('nota_tecnica.aprobar'));

        Livewire::test(TeacherAssignmentComponent::class)
            ->assertSee(__('Technical note — ratification pending from the University Council'));

        $this->assertDatabaseHas('notas_tecnicas', ['estado' => 'Ratificación pendiente']);
    }
}
