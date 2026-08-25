<?php

namespace Tests\Feature\Academic;

use App\Models\AcademicCredential;
use App\Models\AcademicTerm;
use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\Specialty;
use App\Models\Teacher;
use App\Models\TechnicalNote as TechnicalNoteModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Src\Academic\AffinityCatalog\Application\DTOs\AffinityCatalogVersionDTO;
use Src\Academic\AffinityCatalog\Application\UseCases\CreateAffinityCatalogVersionUseCase;
use Src\Academic\TeacherAssignment\Application\DTOs\AttachTechnicalNoteDTO;
use Src\Academic\TeacherAssignment\Application\DTOs\ProposeTeacherAssignmentDTO;
use Src\Academic\TeacherAssignment\Application\UseCases\AttachTechnicalNoteUseCase;
use Src\Academic\TeacherAssignment\Application\UseCases\ExpireOverdueTechnicalNotesUseCase;
use Src\Academic\TeacherAssignment\Application\UseCases\ProposeTeacherAssignmentUseCase;
use Src\Academic\TeacherAssignment\Application\UseCases\RatifyTechnicalNoteUseCase;
use Src\Academic\TeacherAssignment\Application\UseCases\RejectTechnicalNoteUseCase;
use Src\Academic\TeacherAssignment\Domain\Contracts\AffinityVerificationRepositoryInterface;
use Src\Academic\TeacherAssignment\Domain\Contracts\TeacherAssignmentRepositoryInterface;
use Src\Academic\TeacherAssignment\Domain\Contracts\UploadedDocument;
use Src\Academic\TeacherAssignment\Domain\Exceptions\InvalidAssignmentTransitionException;
use Src\Academic\TeacherAssignment\Domain\Exceptions\InvalidTechnicalNoteAttachmentException;
use Src\Academic\TeacherAssignment\Domain\Exceptions\InvalidTechnicalNoteDeadlineException;
use Src\Academic\TeacherAssignment\Domain\ProposalStatus;
use Src\Academic\TeacherAssignment\Domain\VerificationResult;
use Tests\TestCase;

/**
 * DO-02b. D12 is the rubric's explicit "Regular vs Excelente" case: a
 * Technical Note must never overwrite the original verification result.
 *
 * The `test_*_bypassing_presentation` methods below call
 * `AttachTechnicalNoteUseCase` directly (as every method in this class
 * already does — none of them go through `TeacherAssignmentComponent`)
 * to prove the PDF/deadline invariants are enforced at the Application
 * boundary itself, not only by `TechnicalNoteForm`'s Livewire rules
 * (see `TechnicalNoteUploadTest` for the Presentation-level coverage of
 * the same rules).
 */
class TechnicalNoteFlowTest extends TestCase
{
    use RefreshDatabase;

    private function proposeNotMatched(): array
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

        return [$result->assignment->id(), $result->verification];
    }

    private function document(string $mimeType = 'application/pdf'): UploadedDocument
    {
        return new UploadedDocument(
            storagePath: 'technical-notes/test.pdf',
            originalFileName: 'test.pdf',
            mimeType: $mimeType,
            sizeBytes: 1024,
            hashSha256: hash('sha256', 'test'),
        );
    }

    public function test_attaching_a_technical_note_does_not_overwrite_the_original_verification(): void
    {
        [$assignmentId, $originalVerification] = $this->proposeNotMatched();

        app(AttachTechnicalNoteUseCase::class)->handle(new AttachTechnicalNoteDTO(
            teacherAssignmentId: $assignmentId,
            ratificationDeadline: now()->addDays(30)->toDateString(),
            document: $this->document(),
        ), null);

        $verifications = app(AffinityVerificationRepositoryInterface::class)->forAssignment($assignmentId);

        $this->assertCount(2, $verifications);
        $this->assertSame(VerificationResult::NotMatched, $verifications[0]->result());
        $this->assertSame($originalVerification->id(), $verifications[0]->id());
        $this->assertSame(VerificationResult::TechnicalNote, $verifications[1]->result());
    }

    public function test_a_technical_note_cannot_be_attached_when_the_latest_result_is_matched(): void
    {
        $specialty = Specialty::factory()->create();
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
                specialtyIds: [$specialty->id],
            ),
            null,
        );

        $teacher = Teacher::factory()->create();
        AcademicCredential::factory()->create(['docente_id' => $teacher->id, 'especialidad_id' => $specialty->id]);

        $result = app(ProposeTeacherAssignmentUseCase::class)->handle(new ProposeTeacherAssignmentDTO(
            courseGroupId: $group->id,
            teacherId: $teacher->id,
            courseId: $course->id,
            targetDate: '2026-05-01',
        ), null);

        $this->expectException(InvalidAssignmentTransitionException::class);

        app(AttachTechnicalNoteUseCase::class)->handle(new AttachTechnicalNoteDTO(
            teacherAssignmentId: $result->assignment->id(),
            ratificationDeadline: now()->addDays(30)->toDateString(),
            document: $this->document(),
        ), null);
    }

    public function test_ratifying_a_technical_note_confirms_the_assignment(): void
    {
        [$assignmentId] = $this->proposeNotMatched();

        $note = app(AttachTechnicalNoteUseCase::class)->handle(new AttachTechnicalNoteDTO(
            teacherAssignmentId: $assignmentId,
            ratificationDeadline: now()->addDays(30)->toDateString(),
            document: $this->document(),
        ), null);

        app(RatifyTechnicalNoteUseCase::class)->handle($note->id(), null);

        $assignment = app(TeacherAssignmentRepositoryInterface::class)->find($assignmentId);
        $this->assertSame(ProposalStatus::Confirmed, $assignment->status());
    }

    public function test_rejecting_a_technical_note_rejects_the_assignment(): void
    {
        [$assignmentId] = $this->proposeNotMatched();

        $note = app(AttachTechnicalNoteUseCase::class)->handle(new AttachTechnicalNoteDTO(
            teacherAssignmentId: $assignmentId,
            ratificationDeadline: now()->addDays(30)->toDateString(),
            document: $this->document(),
        ), null);

        app(RejectTechnicalNoteUseCase::class)->handle($note->id(), null);

        $assignment = app(TeacherAssignmentRepositoryInterface::class)->find($assignmentId);
        $this->assertSame(ProposalStatus::Rejected, $assignment->status());
    }

    public function test_an_overdue_pending_note_is_automatically_marked_expired(): void
    {
        [$assignmentId] = $this->proposeNotMatched();

        // The deadline must be valid (today-or-future) at creation time —
        // DO-02b's invariant, enforced in AttachTechnicalNoteUseCase — so
        // create it valid, then backdate the persisted row directly to
        // simulate the deadline having since passed (the same way a
        // real note ages past its deadline over time).
        $note = app(AttachTechnicalNoteUseCase::class)->handle(new AttachTechnicalNoteDTO(
            teacherAssignmentId: $assignmentId,
            ratificationDeadline: now()->addDay()->toDateString(),
            document: $this->document(),
        ), null);

        TechnicalNoteModel::query()->whereKey($note->id())->update([
            'fecha_limite_ratificacion' => now()->subDay()->toDateString(),
        ]);

        $expiredCount = app(ExpireOverdueTechnicalNotesUseCase::class)->handle();

        $this->assertSame(1, $expiredCount);
        $this->assertDatabaseHas('notas_tecnicas', [
            'id' => $note->id(),
            'estado' => 'Vencida',
        ]);
    }

    public function test_a_second_technical_note_cannot_be_attached_to_the_same_assignment(): void
    {
        [$assignmentId] = $this->proposeNotMatched();

        app(AttachTechnicalNoteUseCase::class)->handle(new AttachTechnicalNoteDTO(
            teacherAssignmentId: $assignmentId,
            ratificationDeadline: now()->addDays(30)->toDateString(),
            document: $this->document(),
        ), null);

        $this->expectException(InvalidAssignmentTransitionException::class);

        app(AttachTechnicalNoteUseCase::class)->handle(new AttachTechnicalNoteDTO(
            teacherAssignmentId: $assignmentId,
            ratificationDeadline: now()->addDays(30)->toDateString(),
            document: $this->document(),
        ), null);
    }

    public function test_a_non_pdf_attachment_is_rejected_below_presentation(): void
    {
        [$assignmentId] = $this->proposeNotMatched();

        try {
            app(AttachTechnicalNoteUseCase::class)->handle(new AttachTechnicalNoteDTO(
                teacherAssignmentId: $assignmentId,
                ratificationDeadline: now()->addDays(30)->toDateString(),
                document: $this->document('text/plain'),
            ), null);
            $this->fail('Expected InvalidTechnicalNoteAttachmentException was not thrown.');
        } catch (InvalidTechnicalNoteAttachmentException) {
            // Expected — asserted via the catch itself.
        }

        $this->assertDatabaseCount('notas_tecnicas', 0);
    }

    public function test_a_past_deadline_is_rejected_below_presentation(): void
    {
        [$assignmentId] = $this->proposeNotMatched();

        try {
            app(AttachTechnicalNoteUseCase::class)->handle(new AttachTechnicalNoteDTO(
                teacherAssignmentId: $assignmentId,
                ratificationDeadline: now()->subDay()->toDateString(),
                document: $this->document(),
            ), null);
            $this->fail('Expected InvalidTechnicalNoteDeadlineException was not thrown.');
        } catch (InvalidTechnicalNoteDeadlineException) {
            // Expected — asserted via the catch itself.
        }

        $this->assertDatabaseCount('notas_tecnicas', 0);
    }

    public function test_an_empty_deadline_is_rejected_below_presentation(): void
    {
        [$assignmentId] = $this->proposeNotMatched();

        try {
            app(AttachTechnicalNoteUseCase::class)->handle(new AttachTechnicalNoteDTO(
                teacherAssignmentId: $assignmentId,
                ratificationDeadline: '',
                document: $this->document(),
            ), null);
            $this->fail('Expected InvalidTechnicalNoteDeadlineException was not thrown.');
        } catch (InvalidTechnicalNoteDeadlineException) {
            // Expected — asserted via the catch itself.
        }

        $this->assertDatabaseCount('notas_tecnicas', 0);
    }
}
