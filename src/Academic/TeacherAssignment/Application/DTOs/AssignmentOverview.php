<?php

declare(strict_types=1);

namespace Src\Academic\TeacherAssignment\Application\DTOs;

use Src\Academic\TeacherAssignment\Domain\Entities\AffinityVerification;
use Src\Academic\TeacherAssignment\Domain\Entities\TeacherAssignment;
use Src\Academic\TeacherAssignment\Domain\Entities\TechnicalNote;
use Src\Academic\TeacherAssignment\Domain\VerificationResult;

final class AssignmentOverview
{
    public function __construct(
        public readonly TeacherAssignment $assignment,
        public readonly ?AffinityVerification $latestVerification,
        public readonly ?TechnicalNote $technicalNote,
    ) {}

    /**
     * True when editing/deleting this record would silently change or
     * destroy formal history: a Technical Note (which carries the
     * Consejo Universitario's ratification/rejection) or a manual "Sin
     * catálogo" decision (DecideNoCatalogAssignmentUseCase). Shared by
     * EditTeacherAssignmentUseCase and DeleteTeacherAssignmentUseCase so
     * both guards — and the row-level UI hints — agree on one definition.
     */
    public function hasProtectedHistory(): bool
    {
        return $this->technicalNote !== null
            || ($this->latestVerification?->result() === VerificationResult::NoCatalog && $this->assignment->isDecided());
    }
}
