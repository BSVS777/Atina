<?php

declare(strict_types=1);

namespace Src\Academic\TeacherAssignment\Application\UseCases;

use Src\Academic\TeacherAssignment\Domain\Contracts\TeacherAssignmentRepositoryInterface;
use Src\Academic\TeacherAssignment\Domain\Contracts\TechnicalNoteRepositoryInterface;
use Src\Academic\TeacherAssignment\Domain\Entities\TechnicalNote;
use Src\Academic\TeacherAssignment\Domain\Exceptions\InvalidAssignmentTransitionException;
use Src\Academic\TeacherAssignment\Domain\TechnicalNoteStatus;
use Src\Shared\Audit\Domain\Contracts\AuditLogRepositoryInterface;
use Src\Shared\Audit\Domain\Entities\AuditLogEntry;

/**
 * Symmetric to RatifyTechnicalNoteUseCase (D13): the Consejo
 * Universitario can also decline the exceptional path. Gated on
 * `nota_tecnica.aprobar`.
 */
final class RejectTechnicalNoteUseCase
{
    public function __construct(
        private readonly TechnicalNoteRepositoryInterface $notes,
        private readonly TeacherAssignmentRepositoryInterface $assignments,
        private readonly AuditLogRepositoryInterface $auditLog,
    ) {}

    public function handle(int $noteId, ?int $actorUserId): TechnicalNote
    {
        $note = $this->notes->find($noteId);

        if ($note === null || $note->status() !== TechnicalNoteStatus::PendingRatification) {
            throw InvalidAssignmentTransitionException::technicalNoteNotPendingRatification();
        }

        $note->reject();
        $saved = $this->notes->save($note);

        $assignment = $this->assignments->find($note->teacherAssignmentId());
        if ($assignment !== null && ! $assignment->isDecided()) {
            $assignment->reject();
            $this->assignments->save($assignment);
        }

        $this->auditLog->record(new AuditLogEntry(
            actorUserId: $actorUserId,
            auditableType: AttachTechnicalNoteUseCase::AUDITABLE_TYPE,
            auditableId: $noteId,
            action: AuditLogEntry::ACTION_UPDATED,
            changes: ['status' => ['before' => TechnicalNoteStatus::PendingRatification->value, 'after' => TechnicalNoteStatus::Rejected->value]],
        ));

        return $saved;
    }
}
