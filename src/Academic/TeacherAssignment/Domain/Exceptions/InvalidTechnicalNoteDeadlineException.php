<?php

declare(strict_types=1);

namespace Src\Academic\TeacherAssignment\Domain\Exceptions;

use RuntimeException;

/**
 * DO-02b's mandatory ratification-deadline invariant, enforced below
 * Presentation so a Technical Note cannot be persisted with a missing or
 * already-past deadline even if this use case is invoked outside the
 * Livewire form.
 */
final class InvalidTechnicalNoteDeadlineException extends RuntimeException
{
    public static function required(): self
    {
        return new self('A Technical Note requires a ratification deadline.');
    }

    public static function mustNotBeInThePast(): self
    {
        return new self('The Technical Note ratification deadline must be today or a future date.');
    }
}
