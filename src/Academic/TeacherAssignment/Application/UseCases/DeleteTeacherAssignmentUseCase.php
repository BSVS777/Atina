<?php

declare(strict_types=1);

namespace Src\Academic\TeacherAssignment\Application\UseCases;

use Src\Academic\TeacherAssignment\Application\DTOs\AssignmentOverview;
use Src\Academic\TeacherAssignment\Domain\Contracts\AffinityVerificationRepositoryInterface;
use Src\Academic\TeacherAssignment\Domain\Contracts\TeacherAssignmentRepositoryInterface;
use Src\Academic\TeacherAssignment\Domain\Contracts\TechnicalNoteRepositoryInterface;
use Src\Academic\TeacherAssignment\Domain\Exceptions\InvalidAssignmentTransitionException;
use Src\Shared\Audit\Domain\Contracts\AuditLogRepositoryInterface;
use Src\Shared\Audit\Domain\Entities\AuditLogEntry;

/**
 * Corrective-UX delete: removes an accidental proposal/verification
 * (misclick) only when no formal history depends on it — a Technical
 * Note (Council ratification/rejection) or a manual "Sin catálogo"
 * decision block deletion (see AssignmentOverview::hasProtectedHistory()).
 * Once allowed, deleting the `asignaciones_docentes` row cascades to its
 * AffinityVerification trail at the DB level
 * (verificaciones_atinencia.asignacion_docente_id is cascadeOnDelete) —
 * that trail belongs to the same accidental record, and nothing blocks
 * it once we reach this point.
 */
final class DeleteTeacherAssignmentUseCase
{
    public const AUDITABLE_TYPE = 'teacher_assignment';

    public function __construct(
        private readonly TeacherAssignmentRepositoryInterface $assignments,
        private readonly AffinityVerificationRepositoryInterface $verifications,
        private readonly TechnicalNoteRepositoryInterface $notes,
        private readonly AuditLogRepositoryInterface $auditLog,
    ) {}

    public function handle(int $teacherAssignmentId, ?int $actorUserId): void
    {
        $assignment = $this->assignments->find($teacherAssignmentId)
            ?? throw InvalidAssignmentTransitionException::assignmentNotFound();

        $overview = new AssignmentOverview(
            assignment: $assignment,
            latestVerification: $this->verifications->latestForAssignment($teacherAssignmentId),
            technicalNote: $this->notes->forAssignment($teacherAssignmentId),
        );

        if ($overview->hasProtectedHistory()) {
            throw InvalidAssignmentTransitionException::deletionBlockedByProtectedHistory();
        }

        $this->assignments->delete($teacherAssignmentId);

        $this->auditLog->record(new AuditLogEntry(
            actorUserId: $actorUserId,
            auditableType: self::AUDITABLE_TYPE,
            auditableId: $teacherAssignmentId,
            action: AuditLogEntry::ACTION_DELETED,
            changes: [
                'course_group_id' => ['before' => $assignment->courseGroupId(), 'after' => null],
                'teacher_id' => ['before' => $assignment->teacherId(), 'after' => null],
                'result' => ['before' => $overview->latestVerification?->result()->value, 'after' => null],
            ],
        ));
    }
}
