<?php

declare(strict_types=1);

namespace Src\Academic\TeacherAssignment\Application\UseCases;

use Src\Academic\TeacherAssignment\Application\DTOs\AssignmentOverview;
use Src\Academic\TeacherAssignment\Domain\Contracts\AffinityVerificationRepositoryInterface;
use Src\Academic\TeacherAssignment\Domain\Contracts\TeacherAssignmentRepositoryInterface;
use Src\Academic\TeacherAssignment\Domain\Contracts\TechnicalNoteRepositoryInterface;

final class ListTeacherAssignmentsUseCase
{
    public function __construct(
        private readonly TeacherAssignmentRepositoryInterface $assignments,
        private readonly AffinityVerificationRepositoryInterface $verifications,
        private readonly TechnicalNoteRepositoryInterface $notes,
    ) {}

    /**
     * @return array<int, AssignmentOverview>
     */
    public function handle(): array
    {
        return array_map(
            fn ($assignment) => new AssignmentOverview(
                assignment: $assignment,
                latestVerification: $this->verifications->latestForAssignment($assignment->id()),
                technicalNote: $this->notes->forAssignment($assignment->id()),
            ),
            $this->assignments->all(),
        );
    }
}
