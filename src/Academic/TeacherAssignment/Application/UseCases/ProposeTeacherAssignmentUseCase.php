<?php

declare(strict_types=1);

namespace Src\Academic\TeacherAssignment\Application\UseCases;

use Src\Academic\TeacherAssignment\Application\DTOs\AssignmentProposalResult;
use Src\Academic\TeacherAssignment\Application\DTOs\ProposeTeacherAssignmentDTO;
use Src\Academic\TeacherAssignment\Domain\Contracts\TeacherAssignmentRepositoryInterface;
use Src\Academic\TeacherAssignment\Domain\Entities\TeacherAssignment;
use Src\Academic\TeacherAssignment\Domain\ProposalStatus;
use Src\Shared\Audit\Domain\Contracts\AuditLogRepositoryInterface;
use Src\Shared\Audit\Domain\Entities\AuditLogEntry;

/**
 * DO-02a: proposing a teacher for a course group runs the automatic,
 * synchronous affinity verification (RunAffinityVerificationUseCase)
 * against the catalog version DO-02 resolves for the group's term start
 * date, producing exactly one of Atinente / No Atinente / Sin catálogo.
 * ("Nota técnica" is not produced here — it is an explicit escalation via
 * AttachTechnicalNoteUseCase, DO-02b.)
 */
final class ProposeTeacherAssignmentUseCase
{
    public const AUDITABLE_TYPE = 'teacher_assignment';

    public function __construct(
        private readonly TeacherAssignmentRepositoryInterface $assignments,
        private readonly RunAffinityVerificationUseCase $runVerification,
        private readonly AuditLogRepositoryInterface $auditLog,
    ) {}

    public function handle(ProposeTeacherAssignmentDTO $dto, ?int $actorUserId): AssignmentProposalResult
    {
        $assignment = $this->assignments->save(new TeacherAssignment(
            id: null,
            courseGroupId: $dto->courseGroupId,
            teacherId: $dto->teacherId,
            status: ProposalStatus::Proposed,
        ));

        $result = $this->runVerification->handle($assignment, $dto->courseId, $dto->targetDate, $actorUserId);

        $this->auditLog->record(new AuditLogEntry(
            actorUserId: $actorUserId,
            auditableType: self::AUDITABLE_TYPE,
            auditableId: $result->assignment->id(),
            action: AuditLogEntry::ACTION_CREATED,
            changes: [
                'course_group_id' => ['before' => null, 'after' => $dto->courseGroupId],
                'teacher_id' => ['before' => null, 'after' => $dto->teacherId],
                'result' => ['before' => null, 'after' => $result->verification->result()->value],
                'status' => ['before' => null, 'after' => $result->assignment->status()->value],
            ],
        ));

        return $result;
    }
}
