<?php

declare(strict_types=1);

namespace Src\Academic\TeacherAssignment\Application\UseCases;

use DateTimeImmutable;
use Src\Academic\TeacherAssignment\Domain\Contracts\TeacherAssignmentRepositoryInterface;
use Src\Academic\TeacherAssignment\Domain\Contracts\TechnicalNoteRepositoryInterface;
use Src\Academic\TeacherAssignment\Domain\Entities\TechnicalNote;
use Src\Academic\TeacherAssignment\Domain\Exceptions\InvalidAssignmentTransitionException;
use Src\Academic\TeacherAssignment\Domain\TechnicalNoteStatus;
use Src\Shared\Audit\Domain\Contracts\AuditLogRepositoryInterface;
use Src\Shared\Audit\Domain\Entities\AuditLogEntry;

/**
 * D13 (Docs/DIARIO_DECISIONES_IA.md): the SRS describes the SLA
 * expiration but not how a successful ratification by the Consejo
 * Universitario is recorded — the professor-provided database resolves
 * this with an explicit `notas_tecnicas.estado = 'Ratificada'` value, so
 * this use case exists to record that manual decision. Gated on
 * `nota_tecnica.aprobar` (Administrador only per the official
 * permission_role matrix).
 */
final class RatifyTechnicalNoteUseCase
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

        $note->ratify(new DateTimeImmutable);
        $saved = $this->notes->save($note);

        $assignment = $this->assignments->find($note->teacherAssignmentId());
        if ($assignment !== null && ! $assignment->isDecided()) {
            $assignment->confirm();
            $this->assignments->save($assignment);
        }

        $this->auditLog->record(new AuditLogEntry(
            actorUserId: $actorUserId,
            auditableType: AttachTechnicalNoteUseCase::AUDITABLE_TYPE,
            auditableId: $noteId,
            action: AuditLogEntry::ACTION_UPDATED,
            changes: ['status' => ['before' => TechnicalNoteStatus::PendingRatification->value, 'after' => TechnicalNoteStatus::Ratified->value]],
        ));

        return $saved;
    }
}
