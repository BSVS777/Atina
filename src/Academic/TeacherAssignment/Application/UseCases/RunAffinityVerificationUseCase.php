<?php

declare(strict_types=1);

namespace Src\Academic\TeacherAssignment\Application\UseCases;

use DateTimeImmutable;
use Src\Academic\AcademicCredential\Domain\Contracts\AcademicCredentialRepositoryInterface;
use Src\Academic\AffinityCatalog\Application\UseCases\ResolveApplicableCatalogVersionUseCase;
use Src\Academic\TeacherAssignment\Application\DTOs\AssignmentProposalResult;
use Src\Academic\TeacherAssignment\Domain\Contracts\AffinityVerificationRepositoryInterface;
use Src\Academic\TeacherAssignment\Domain\Contracts\TeacherAssignmentRepositoryInterface;
use Src\Academic\TeacherAssignment\Domain\Entities\AffinityVerification;
use Src\Academic\TeacherAssignment\Domain\Entities\TeacherAssignment;
use Src\Academic\TeacherAssignment\Domain\VerificationResult;

/**
 * The DO-02a affinity-matching algorithm (resolve the applicable catalog
 * version, look for a matching credential, auto-confirm on a match),
 * factored out of ProposeTeacherAssignmentUseCase so
 * EditTeacherAssignmentUseCase can rerun the exact same logic against a
 * corrected teacher/course-group without duplicating it.
 */
final class RunAffinityVerificationUseCase
{
    public function __construct(
        private readonly TeacherAssignmentRepositoryInterface $assignments,
        private readonly AffinityVerificationRepositoryInterface $verifications,
        private readonly AcademicCredentialRepositoryInterface $credentials,
        private readonly ResolveApplicableCatalogVersionUseCase $resolveCatalogVersion,
    ) {}

    public function handle(TeacherAssignment $assignment, int $courseId, string $targetDate, ?int $actorUserId): AssignmentProposalResult
    {
        $assignmentId = $assignment->id() ?? throw new \LogicException('The assignment must already be persisted before running a verification.');

        $resolved = $this->resolveCatalogVersion->handle($courseId, new DateTimeImmutable($targetDate));

        if ($resolved === null) {
            $result = VerificationResult::NoCatalog;
            $catalogVersionId = null;
            $matchedCredentialId = null;
            $isProvisional = false;
        } else {
            $matchedCredential = null;

            foreach ($this->credentials->forTeacher($assignment->teacherId()) as $credential) {
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

        return new AssignmentProposalResult($assignment, $verification);
    }
}
