<?php

declare(strict_types=1);

namespace Src\Academic\TeacherAssignment\Application\UseCases;

use DateTimeImmutable;
use Src\Academic\TeacherAssignment\Domain\Contracts\TechnicalNoteRepositoryInterface;
use Src\Academic\TeacherAssignment\Domain\TechnicalNoteStatus;
use Src\Shared\Audit\Domain\Contracts\AuditLogRepositoryInterface;
use Src\Shared\Audit\Domain\Entities\AuditLogEntry;

/**
 * DO-02b: "El sistema marca automáticamente como vencida toda Nota
 * técnica cuya fecha límite ya pasó sin resolución registrada." Terminal
 * (D14) — does not touch the underlying assignment's status.
 */
final class ExpireOverdueTechnicalNotesUseCase
{
    public function __construct(
        private readonly TechnicalNoteRepositoryInterface $notes,
        private readonly AuditLogRepositoryInterface $auditLog,
    ) {}

    public function handle(): int
    {
        $now = new DateTimeImmutable;
        $expired = 0;

        foreach ($this->notes->pendingRatification() as $note) {
            if (! $note->isOverdue($now)) {
                continue;
            }

            $note->expire();
            $this->notes->save($note);

            $this->auditLog->record(new AuditLogEntry(
                actorUserId: null,
                auditableType: AttachTechnicalNoteUseCase::AUDITABLE_TYPE,
                auditableId: $note->id() ?? 0,
                action: AuditLogEntry::ACTION_UPDATED,
                changes: ['status' => ['before' => TechnicalNoteStatus::PendingRatification->value, 'after' => TechnicalNoteStatus::Expired->value]],
            ));

            $expired++;
        }

        return $expired;
    }
}
