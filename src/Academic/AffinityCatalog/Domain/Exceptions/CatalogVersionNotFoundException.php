<?php

declare(strict_types=1);

namespace Src\Academic\AffinityCatalog\Domain\Exceptions;

use RuntimeException;

final class CatalogVersionNotFoundException extends RuntimeException
{
    public static function withId(int $id): self
    {
        return new self("No catalog version found with id #{$id}.");
    }
}
