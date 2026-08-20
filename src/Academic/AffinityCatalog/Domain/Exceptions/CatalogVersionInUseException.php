<?php

declare(strict_types=1);

namespace Src\Academic\AffinityCatalog\Domain\Exceptions;

use RuntimeException;

/**
 * DO-02: historical verifications cite the catalog version applied at
 * the time they ran (`AffinityVerification.catalogVersionId`, immutable).
 * Editing a version already cited by one would silently rewrite what
 * that citation shows, so once in use a version can only be superseded
 * by a brand new one — never edited in place.
 */
final class CatalogVersionInUseException extends RuntimeException
{
    public static function withId(int $id): self
    {
        return new self("Catalog version #{$id} already has verifications recorded against it and can no longer be edited — publish a new version instead.");
    }
}
