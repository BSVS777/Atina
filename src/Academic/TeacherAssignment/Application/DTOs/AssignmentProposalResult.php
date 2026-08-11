<?php

declare(strict_types=1);

namespace Src\Academic\TeacherAssignment\Application\DTOs;

use Src\Academic\TeacherAssignment\Domain\Entities\AffinityVerification;
use Src\Academic\TeacherAssignment\Domain\Entities\TeacherAssignment;

final class AssignmentProposalResult
{
    public function __construct(
        public readonly TeacherAssignment $assignment,
        public readonly AffinityVerification $verification,
    ) {}
}
