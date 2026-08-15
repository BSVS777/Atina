<?php

declare(strict_types=1);

namespace Src\Academic\AcademicCredential\Domain\Exceptions;

use DomainException;

final class CredentialNotFoundException extends DomainException
{
    public static function withId(int $id): self
    {
        return new self("Academic credential with id {$id} was not found.");
    }
}
