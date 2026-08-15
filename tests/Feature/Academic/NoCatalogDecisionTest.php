<?php

namespace Tests\Feature\Academic;

use App\Models\AcademicTerm;
use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\Teacher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Src\Academic\TeacherAssignment\Application\DTOs\ProposeTeacherAssignmentDTO;
use Src\Academic\TeacherAssignment\Application\UseCases\DecideNoCatalogAssignmentUseCase;
use Src\Academic\TeacherAssignment\Application\UseCases\ProposeTeacherAssignmentUseCase;
use Src\Academic\TeacherAssignment\Domain\Exceptions\InvalidAssignmentTransitionException;
use Src\Academic\TeacherAssignment\Domain\ProposalStatus;
use Tests\TestCase;

/**
 * DO-02d: careers/courses without a published catalog stay
 * "Pendiente de aprobación manual" until the Coordinadora decides.
 */
class NoCatalogDecisionTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_approving_a_no_catalog_assignment_confirms_it(): void
    {
        $assignmentId = $this->proposeWithoutCatalog();

        $assignment = app(DecideNoCatalogAssignmentUseCase::class)->handle($assignmentId, approve: true, actorUserId: null);

        $this->assertSame(ProposalStatus::Confirmed, $assignment->status());
    }

    public function test_rejecting_a_no_catalog_assignment_rejects_it(): void
    {
        $assignmentId = $this->proposeWithoutCatalog();

        $assignment = app(DecideNoCatalogAssignmentUseCase::class)->handle($assignmentId, approve: false, actorUserId: null);

        $this->assertSame(ProposalStatus::Rejected, $assignment->status());
    }

    public function test_a_decided_assignment_cannot_be_decided_again(): void
    {
        $assignmentId = $this->proposeWithoutCatalog();
        app(DecideNoCatalogAssignmentUseCase::class)->handle($assignmentId, approve: true, actorUserId: null);

        $this->expectException(InvalidAssignmentTransitionException::class);

        app(DecideNoCatalogAssignmentUseCase::class)->handle($assignmentId, approve: false, actorUserId: null);
    }

    public function test_the_decision_is_recorded_in_the_audit_log(): void
    {
        $assignmentId = $this->proposeWithoutCatalog();

        app(DecideNoCatalogAssignmentUseCase::class)->handle($assignmentId, approve: true, actorUserId: null);

        $this->assertDatabaseHas('auditorias', [
            'auditable_type' => 'teacher_assignment',
            'auditable_id' => $assignmentId,
            'accion' => 'Modificación',
        ]);
    }
}
