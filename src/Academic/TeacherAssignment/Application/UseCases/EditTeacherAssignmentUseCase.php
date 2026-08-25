<?php

declare(strict_types=1);

namespace Src\Academic\TeacherAssignment\Application\UseCases;

use Src\Academic\TeacherAssignment\Application\DTOs\AssignmentOverview;
use Src\Academic\TeacherAssignment\Application\DTOs\AssignmentProposalResult;
use Src\Academic\TeacherAssignment\Application\DTOs\ProposeTeacherAssignmentDTO;
use Src\Academic\TeacherAssignment\Domain\Contracts\AffinityVerificationRepositoryInterface;
use Src\Academic\TeacherAssignment\Domain\Contracts\TeacherAssignmentRepositoryInterface;
use Src\Academic\TeacherAssignment\Domain\Contracts\TechnicalNoteRepositoryInterface;
use Src\Academic\TeacherAssignment\Domain\Entities\TeacherAssignment;
use Src\Academic\TeacherAssignment\Domain\Exceptions\InvalidAssignmentTransitionException;
use Src\Academic\TeacherAssignment\Domain\ProposalStatus;
use Src\Shared\Audit\Domain\Contracts\AuditLogRepositoryInterface;
use Src\Shared\Audit\Domain\Entities\AuditLogEntry;

/**
 * Corrective-UX edit: lets an authorized user fix a misclicked
 * teacher/course-group on an accidental proposal. It never sets Atinente
 * / No Atinente / Nota técnica / Sin catálogo directly — changing either
 * field resets the assignment to Proposed and reruns
 * RunAffinityVerificationUseCase (the same algorithm
 * ProposeTeacherAssignmentUseCase uses), which appends a new verification
 * event instead of mutating the previous one (D11/D12). Blocked once the
 * assignment has protected history — see
 * AssignmentOverview::hasProtectedHistory().
 */
final class EditTeacherAssignmentUseCase
{
    public const AUDITABLE_TYPE = 'teacher_assignment';

    public function __construct(
        private readonly TeacherAssignmentRepositoryInterface $assignments,
        private readonly AffinityVerificationRepositoryInterface $verifications,
        private readonly TechnicalNoteRepositoryInterface $notes,
        private readonly RunAffinityVerificationUseCase $runVerification,
        private readonly AuditLogRepositoryInterface $auditLog,
    ) {}

    public function handle(int $teacherAssignmentId, ProposeTeacherAssignmentDTO $dto, ?int $actorUserId): AssignmentProposalResult
    {
        $assignment = $this->assignments->find($teacherAssignmentId)
            ?? throw InvalidAssignmentTransitionException::assignmentNotFound();

        $overview = new AssignmentOverview(
            assignment: $assignment,
            latestVerification: $this->verifications->latestForAssignment($teacherAssignmentId),
            technicalNote: $this->notes->forAssignment($teacherAssignmentId),
        );

        if ($overview->hasProtectedHistory()) {
            throw InvalidAssignmentTransitionException::editBlockedByProtectedHistory();
        }

        $before = [
            'course_group_id' => $assignment->courseGroupId(),
            'teacher_id' => $assignment->teacherId(),
            'status' => $assignment->status()->value,
            'result' => $overview->latestVerification?->result()->value,
        ];

        $updated = $this->assignments->save(new TeacherAssignment(
            id: $teacherAssignmentId,
            courseGroupId: $dto->courseGroupId,
            teacherId: $dto->teacherId,
            status: ProposalStatus::Proposed,
        ));

        $result = $this->runVerification->handle($updated, $dto->courseId, $dto->targetDate, $actorUserId);

        $this->auditLog->record(new AuditLogEntry(
            actorUserId: $actorUserId,
            auditableType: self::AUDITABLE_TYPE,
            auditableId: $teacherAssignmentId,
            action: AuditLogEntry::ACTION_UPDATED,
            changes: [
                'course_group_id' => ['before' => $before['course_group_id'], 'after' => $dto->courseGroupId],
                'teacher_id' => ['before' => $before['teacher_id'], 'after' => $dto->teacherId],
                'result' => ['before' => $before['result'], 'after' => $result->verification->result()->value],
                'status' => ['before' => $before['status'], 'after' => $result->assignment->status()->value],
            ],
        ));

        return $result;
    }
}
