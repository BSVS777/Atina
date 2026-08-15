<?php

declare(strict_types=1);

namespace Src\Academic\TeacherAssignment\Application\UseCases;

use DateTimeImmutable;
use Src\Academic\AcademicCredential\Domain\Contracts\AcademicCredentialRepositoryInterface;
use Src\Academic\AffinityCatalog\Application\UseCases\ResolveApplicableCatalogVersionUseCase;
use Src\Academic\TeacherAssignment\Application\DTOs\AssignmentProposalResult;
use Src\Academic\TeacherAssignment\Application\DTOs\ProposeTeacherAssignmentDTO;
use Src\Academic\TeacherAssignment\Domain\Contracts\AffinityVerificationRepositoryInterface;
use Src\Academic\TeacherAssignment\Domain\Contracts\TeacherAssignmentRepositoryInterface;
use Src\Academic\TeacherAssignment\Domain\Entities\AffinityVerification;
use Src\Academic\TeacherAssignment\Domain\Entities\TeacherAssignment;
use Src\Academic\TeacherAssignment\Domain\ProposalStatus;
use Src\Academic\TeacherAssignment\Domain\VerificationResult;
use Src\Shared\Audit\Domain\Contracts\AuditLogRepositoryInterface;
use Src\Shared\Audit\Domain\Entities\AuditLogEntry;

/**
 * DO-02a: proposing a teacher for a course group runs the automatic,
 * synchronous affinity verification against the catalog version DO-02
 * resolves for the group's term start date, producing exactly one of
 * Atinente / No Atinente / Sin catálogo. ("Nota técnica" is not produced
 * here — it is an explicit escalation via AttachTechnicalNoteUseCase,
 * DO-02b.)
 */
final class ProposeTeacherAssignmentUseCase
{
    public const AUDITABLE_TYPE = 'teacher_assignment';

    public function __construct(
        private readonly TeacherAssignmentRepositoryInterface $assignments,
        private readonly AffinityVerificationRepositoryInterface $verifications,
        private readonly AcademicCredentialRepositoryInterface $credentials,
        private readonly ResolveApplicableCatalogVersionUseCase $resolveCatalogVersion,
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
        $assignmentId = $assignment->id() ?? throw new \LogicException('The repository must return the saved assignment with an id.');

        $resolved = $this->resolveCatalogVersion->handle($dto->courseId, new DateTimeImmutable($dto->targetDate));

        if ($resolved === null) {
            $result = VerificationResult::NoCatalog;
            $catalogVersionId = null;
            $matchedCredentialId = null;
            $isProvisional = false;
        } else {
            $matchedCredential = null;

            foreach ($this->credentials->forTeacher($dto->teacherId) as $credential) {
                if ($resolved->version->isAffineToSpecialty($credential->specialtyId())) {
                    $matchedCredential = $credential;
                    break;
                }
            }

            $result = $matchedCredential !== null ? VerificationResult::Matched : VerificationResult::NotMatched;
            $catalogVersionId = $resolved->version->id();
            $matchedCredentialId = $matchedCredential?->id();
            $isProvisional = $resolved->isProvisional;

            if ($matchedCredential !== null) {
                $assignment->confirm();
                $assignment = $this->assignments->save($assignment);
            }
        }

        $verification = $this->verifications->save(new AffinityVerification(
            id: null,
            teacherAssignmentId: $assignmentId,
            catalogVersionId: $catalogVersionId,
            matchedCredentialId: $matchedCredentialId,
            performedByUserId: $actorUserId,
            result: $result,
            isProvisional: $isProvisional,
            justification: null,
            performedAt: new DateTimeImmutable,
        ));

        $this->auditLog->record(new AuditLogEntry(
            actorUserId: $actorUserId,
            auditableType: self::AUDITABLE_TYPE,
            auditableId: $assignmentId,
            action: AuditLogEntry::ACTION_CREATED,
            changes: [
                'course_group_id' => ['before' => null, 'after' => $dto->courseGroupId],
                'teacher_id' => ['before' => null, 'after' => $dto->teacherId],
                'result' => ['before' => null, 'after' => $result->value],
                'status' => ['before' => null, 'after' => $assignment->status()->value],
            ],
        ));

        return new AssignmentProposalResult($assignment, $verification);
    }
}
