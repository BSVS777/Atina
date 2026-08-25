<?php

declare(strict_types=1);

namespace Src\Academic\TeacherAssignment\Domain\Exceptions;

use RuntimeException;

/**
 * DO-02b's mandatory signed-PDF invariant, enforced below Presentation so
 * a Technical Note cannot be persisted with a non-PDF attachment even if
 * this use case is invoked outside the Livewire form.
 */
final class InvalidTechnicalNoteAttachmentException extends RuntimeException
{
    public static function mustBeAPdf(string $mimeType): self
    {
        return new self("The technical criterion document must be a signed PDF (received: {$mimeType}).");
    }
}
