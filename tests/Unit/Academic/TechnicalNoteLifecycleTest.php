<?php

namespace Tests\Unit\Academic;

use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Src\Academic\TeacherAssignment\Application\UseCases\ExpireOverdueTechnicalNotesUseCase;
use Src\Academic\TeacherAssignment\Application\UseCases\RatifyTechnicalNoteUseCase;
use Src\Academic\TeacherAssignment\Application\UseCases\RejectTechnicalNoteUseCase;
use Src\Academic\TeacherAssignment\Domain\Entities\TeacherAssignment;
use Src\Academic\TeacherAssignment\Domain\Entities\TechnicalNote;
use Src\Academic\TeacherAssignment\Domain\Exceptions\InvalidAssignmentTransitionException;
use Src\Academic\TeacherAssignment\Domain\ProposalStatus;
use Src\Academic\TeacherAssignment\Domain\TechnicalNoteStatus;
use Tests\Unit\Academic\Fakes\InMemoryAuditLogRepository;
use Tests\Unit\Academic\Fakes\InMemoryTeacherAssignmentRepository;
use Tests\Unit\Academic\Fakes\InMemoryTechnicalNoteRepository;

/**
 * DO-02b's Technical Note state machine: only a note pending
 * ratification can be ratified, rejected, or expired, and "Expired" is
 * terminal (D14). Every transition is driven through the use cases that
 * own the guard — the entity itself is only asserted for the invariants
 * it genuinely enforces.
 */
class TechnicalNoteLifecycleTest extends TestCase
{
    private const ASSIGNMENT_ID = 1;

    private InMemoryTechnicalNoteRepository $notes;

    private InMemoryTeacherAssignmentRepository $assignments;

    private InMemoryAuditLogRepository $auditLog;

    protected function setUp(): void
    {
        parent::setUp();

        $this->notes = new InMemoryTechnicalNoteRepository;
        $this->assignments = new InMemoryTeacherAssignmentRepository;
        $this->auditLog = new InMemoryAuditLogRepository;
    }

    public function test_a_note_without_an_attached_document_cannot_exist(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new TechnicalNote(
            id: null,
            teacherAssignmentId: self::ASSIGNMENT_ID,
            documentPath: '   ',
            submittedByUserId: null,
            ratificationDeadline: new DateTimeImmutable('2026-12-31'),
            status: TechnicalNoteStatus::PendingRatification,
            ratifiedAt: null,
        );
    }

    public function test_a_pending_note_past_its_deadline_is_overdue(): void
    {
        $note = $this->note(TechnicalNoteStatus::PendingRatification, deadline: '2026-01-31');

        $this->assertTrue($note->isOverdue(new DateTimeImmutable('2026-02-01')));
    }

    public function test_a_pending_note_is_not_overdue_on_its_deadline(): void
    {
        $note = $this->note(TechnicalNoteStatus::PendingRatification, deadline: '2026-01-31');

        $this->assertFalse($note->isOverdue(new DateTimeImmutable('2026-01-31')));
    }

    #[DataProvider('resolvedStatuses')]
    public function test_a_note_that_is_no_longer_pending_is_never_overdue(TechnicalNoteStatus $status): void
    {
        $note = $this->note($status, deadline: '2026-01-31');

        $this->assertFalse($note->isOverdue(new DateTimeImmutable('2030-01-01')));
    }

    /**
     * @return array<string, array{TechnicalNoteStatus}>
     */
    public static function resolvedStatuses(): array
    {
        return [
            'ratified' => [TechnicalNoteStatus::Ratified],
            'rejected' => [TechnicalNoteStatus::Rejected],
            'expired' => [TechnicalNoteStatus::Expired],
        ];
    }

    public function test_ratifying_a_pending_note_confirms_the_underlying_assignment(): void
    {
        $note = $this->persistNote(TechnicalNoteStatus::PendingRatification);
        $this->persistAssignment(ProposalStatus::Proposed);

        $ratified = $this->ratifyUseCase()->handle($note->id(), actorUserId: 4);

        $this->assertSame(TechnicalNoteStatus::Ratified, $ratified->status());
        $this->assertNotNull($ratified->ratifiedAt());
        $this->assertSame(ProposalStatus::Confirmed, $this->assignments->find(self::ASSIGNMENT_ID)->status());
    }

    public function test_rejecting_a_pending_note_rejects_the_underlying_assignment(): void
    {
        $note = $this->persistNote(TechnicalNoteStatus::PendingRatification);
        $this->persistAssignment(ProposalStatus::Proposed);

        $rejected = $this->rejectUseCase()->handle($note->id(), actorUserId: 4);

        $this->assertSame(TechnicalNoteStatus::Rejected, $rejected->status());
        $this->assertNull($rejected->ratifiedAt());
        $this->assertSame(ProposalStatus::Rejected, $this->assignments->find(self::ASSIGNMENT_ID)->status());
    }

    public function test_ratifying_never_reopens_an_assignment_that_was_already_decided(): void
    {
        $note = $this->persistNote(TechnicalNoteStatus::PendingRatification);
        $this->persistAssignment(ProposalStatus::Rejected);

        $this->ratifyUseCase()->handle($note->id(), actorUserId: null);

        $this->assertSame(ProposalStatus::Rejected, $this->assignments->find(self::ASSIGNMENT_ID)->status());
    }

    #[DataProvider('resolvedStatuses')]
    public function test_only_a_pending_note_can_be_ratified(TechnicalNoteStatus $status): void
    {
        $note = $this->persistNote($status);
        $this->persistAssignment(ProposalStatus::Proposed);

        $this->expectException(InvalidAssignmentTransitionException::class);

        $this->ratifyUseCase()->handle($note->id(), actorUserId: null);
    }

    #[DataProvider('resolvedStatuses')]
    public function test_only_a_pending_note_can_be_rejected(TechnicalNoteStatus $status): void
    {
        $note = $this->persistNote($status);
        $this->persistAssignment(ProposalStatus::Proposed);

        $this->expectException(InvalidAssignmentTransitionException::class);

        $this->rejectUseCase()->handle($note->id(), actorUserId: null);
    }

    public function test_ratifying_a_note_that_does_not_exist_is_refused(): void
    {
        $this->expectException(InvalidAssignmentTransitionException::class);

        $this->ratifyUseCase()->handle(404, actorUserId: null);
    }

    public function test_expiring_only_touches_pending_notes_whose_deadline_already_passed(): void
    {
        $overdue = $this->persistNote(TechnicalNoteStatus::PendingRatification, deadline: '2000-01-01');
        $stillOpen = $this->persistNote(TechnicalNoteStatus::PendingRatification, deadline: '2999-01-01', assignmentId: 2);
        $alreadyRatified = $this->persistNote(TechnicalNoteStatus::Ratified, deadline: '2000-01-01', assignmentId: 3);

        $expired = (new ExpireOverdueTechnicalNotesUseCase($this->notes, $this->auditLog))->handle();

        $this->assertSame(1, $expired);
        $this->assertSame(TechnicalNoteStatus::Expired, $this->notes->find($overdue->id())->status());
        $this->assertSame(TechnicalNoteStatus::PendingRatification, $this->notes->find($stillOpen->id())->status());
        $this->assertSame(TechnicalNoteStatus::Ratified, $this->notes->find($alreadyRatified->id())->status());
    }

    public function test_expiring_is_idempotent_because_expired_is_terminal(): void
    {
        $this->persistNote(TechnicalNoteStatus::PendingRatification, deadline: '2000-01-01');
        $useCase = new ExpireOverdueTechnicalNotesUseCase($this->notes, $this->auditLog);

        $useCase->handle();

        $this->assertSame(0, $useCase->handle());
        $this->assertCount(1, $this->auditLog->entries());
    }

    private function ratifyUseCase(): RatifyTechnicalNoteUseCase
    {
        return new RatifyTechnicalNoteUseCase($this->notes, $this->assignments, $this->auditLog);
    }

    private function rejectUseCase(): RejectTechnicalNoteUseCase
    {
        return new RejectTechnicalNoteUseCase($this->notes, $this->assignments, $this->auditLog);
    }

    private function persistAssignment(ProposalStatus $status): TeacherAssignment
    {
        return $this->assignments->save(new TeacherAssignment(
            id: self::ASSIGNMENT_ID,
            courseGroupId: 1,
            teacherId: 1,
            status: $status,
        ));
    }

    private function persistNote(TechnicalNoteStatus $status, string $deadline = '2026-12-31', int $assignmentId = self::ASSIGNMENT_ID): TechnicalNote
    {
        return $this->notes->save($this->note($status, $deadline, $assignmentId));
    }

    private function note(TechnicalNoteStatus $status, string $deadline = '2026-12-31', int $assignmentId = self::ASSIGNMENT_ID): TechnicalNote
    {
        return new TechnicalNote(
            id: null,
            teacherAssignmentId: $assignmentId,
            documentPath: 'technical-notes/criterion.pdf',
            submittedByUserId: null,
            ratificationDeadline: new DateTimeImmutable($deadline),
            status: $status,
            ratifiedAt: null,
        );
    }
}
