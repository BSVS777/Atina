<?php

namespace Tests\Unit\Academic\Fakes;

use Src\Academic\TeacherAssignment\Domain\Contracts\AffinityVerificationRepositoryInterface;
use Src\Academic\TeacherAssignment\Domain\Entities\AffinityVerification;

final class InMemoryAffinityVerificationRepository implements AffinityVerificationRepositoryInterface
{
    /** @var list<AffinityVerification> */
    private array $verifications = [];

    private int $nextId = 1;

    public function forAssignment(int $teacherAssignmentId): array
    {
        return array_values(array_filter(
            $this->verifications,
            fn (AffinityVerification $verification) => $verification->teacherAssignmentId() === $teacherAssignmentId,
        ));
    }

    public function latestForAssignment(int $teacherAssignmentId): ?AffinityVerification
    {
        $forAssignment = $this->forAssignment($teacherAssignmentId);

        return $forAssignment === [] ? null : $forAssignment[count($forAssignment) - 1];
    }

    public function save(AffinityVerification $verification): AffinityVerification
    {
        $saved = $verification->id() === null ? $verification->withId($this->nextId++) : $verification;
        $this->verifications[] = $saved;

        return $saved;
    }
}
