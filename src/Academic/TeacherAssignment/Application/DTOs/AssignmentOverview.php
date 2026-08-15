<?php

declare(strict_types=1);

namespace Src\Academic\TeacherAssignment\Application\DTOs;

use Src\Academic\TeacherAssignment\Domain\Entities\AffinityVerification;
use Src\Academic\TeacherAssignment\Domain\Entities\TeacherAssignment;
use Src\Academic\TeacherAssignment\Domain\Entities\TechnicalNote;

final class AssignmentOverview
{
    public function __construct(
        public readonly TeacherAssignment $assignment,
        public readonly ?AffinityVerification $latestVerification,
        public readonly ?TechnicalNote $technicalNote,
    ) {}
}
