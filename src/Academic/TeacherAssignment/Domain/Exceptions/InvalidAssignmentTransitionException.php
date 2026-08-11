<?php

declare(strict_types=1);

namespace Src\Academic\TeacherAssignment\Domain\Exceptions;

use RuntimeException;

final class InvalidAssignmentTransitionException extends RuntimeException
{
    public static function technicalNoteRequiresNotMatchedOrNoCatalog(): self
    {
        return new self('A Technical Note can only be attached to an assignment whose latest verification is "No Atinente" or "Sin catálogo".');
    }

    public static function noCatalogDecisionRequiresNoCatalogResult(): self
    {
        return new self('A manual no-catalog decision can only be made when the latest verification is "Sin catálogo".');
    }

    public static function assignmentAlreadyDecided(): self
    {
        return new self('This assignment has already been decided (confirmed or rejected).');
    }

    public static function technicalNoteAlreadyExists(): self
    {
        return new self('This assignment already has a Technical Note — start a new assignment to retry.');
    }

    public static function technicalNoteNotPendingRatification(): self
    {
        return new self('Only a Technical Note pending ratification can be ratified, rejected, or expired.');
    }
}
