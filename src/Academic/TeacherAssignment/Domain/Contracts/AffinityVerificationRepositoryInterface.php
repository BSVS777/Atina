<?php

declare(strict_types=1);

namespace Src\Academic\TeacherAssignment\Domain\Contracts;

use Src\Academic\TeacherAssignment\Domain\Entities\AffinityVerification;

interface AffinityVerificationRepositoryInterface
{
    /**
     * @return array<int, AffinityVerification> Ordered oldest to newest.
     */
    public function forAssignment(int $teacherAssignmentId): array;

    public function latestForAssignment(int $teacherAssignmentId): ?AffinityVerification;

    public function save(AffinityVerification $verification): AffinityVerification;
}
