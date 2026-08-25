<?php

declare(strict_types=1);

namespace Src\Academic\AcademicCredential\Domain\Exceptions;

use DomainException;

/**
 * Raised when the institution-search provider cannot be reached or
 * returns something unusable (connection failure, timeout, 4xx/429/5xx,
 * malformed body, unexpected shape). Institution search is enrichment
 * only, so every caller must catch this and fall back to manual entry —
 * it must never block credential creation/editing.
 */
final class InstitutionSearchUnavailableException extends DomainException
{
    public static function make(): self
    {
        return new self('Institution search provider is unavailable.');
    }
}
