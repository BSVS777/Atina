<?php

declare(strict_types=1);

namespace Src\Academic\AffinityCatalog\Domain\Exceptions;

use RuntimeException;

/**
 * DO-02 / D7 (Docs/DIARIO_DECISIONES_IA.md): two versions of the same
 * course cannot have overlapping validity ranges — otherwise DO-02a
 * would find more than one "applicable" version for the same date.
 */
final class OverlappingCatalogVersionException extends RuntimeException
{
    public static function forCourse(int $courseId): self
    {
        return new self("The new validity range overlaps an existing catalog version for course #{$courseId}.");
    }
}
