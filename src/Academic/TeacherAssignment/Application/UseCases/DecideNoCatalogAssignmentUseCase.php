<?php

declare(strict_types=1);

namespace Src\Academic\TeacherAssignment\Application\UseCases;

use Src\Academic\TeacherAssignment\Domain\Contracts\AffinityVerificationRepositoryInterface;
use Src\Academic\TeacherAssignment\Domain\Contracts\TeacherAssignmentRepositoryInterface;
use Src\Academic\TeacherAssignment\Domain\Entities\TeacherAssignment;
use Src\Academic\TeacherAssignment\Domain\Exceptions\InvalidAssignmentTransitionException;
use Src\Academic\TeacherAssignment\Domain\VerificationResult;
use Src\Shared\Audit\Domain\Contracts\AuditLogRepositoryInterface;
use Src\Shared\Audit\Domain\Entities\AuditLogEntry;

/**
 * DO-02d: when a career/course has no published catalog, the assignment
 * stays "Pendiente de aprobación manual" until the Coordinadora de
 * Docencia approves or rejects it directly (distinct from the Technical
 * Note path, DO-02b, which is also available for the same "Sin
 * catálogo" result).
 */
final class DecideNoCatalogAssignmentUseCase
{
    public const AUDITABLE_TYPE = 'teacher_assignment';

    public function __construct(
        private readonly TeacherAssignmentRepositoryInterface $assignments,
        private readonly AffinityVerificationRepositoryInterface $verifications,
        private readonly AuditLogRepositoryInterface $auditLog,
    ) {}

    public function handle(int $teacherAssignmentId, bool $approve, ?int $actorUserId): TeacherAssignment
    {
        $latest = $this->verifications->latestForAssignment($teacherAssignmentId);

        if ($latest === null || $latest->result() !== VerificationResult::NoCatalog) {
            throw InvalidAssignmentTransitionException::noCatalogDecisionRequiresNoCatalogResult();
        }

        $assignment = $this->assignments->find($teacherAssignmentId)
            ?? throw InvalidAssignmentTransitionException::noCatalogDecisionRequiresNoCatalogResult();

        if ($assignment->isDecided()) {
            throw InvalidAssignmentTransitionException::assignmentAlreadyDecided();
        }

        $approve ? $assignment->confirm() : $assignment->reject();
        $saved = $this->assignments->save($assignment);

        $this->auditLog->record(new AuditLogEntry(
            actorUserId: $actorUserId,
            auditableType: self::AUDITABLE_TYPE,
            auditableId: $teacherAssignmentId,
            action: AuditLogEntry::ACTION_UPDATED,
            changes: ['status' => ['before' => 'proposed', 'after' => $saved->status()->value]],
        ));

        return $saved;
    }
}
