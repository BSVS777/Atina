<?php

declare(strict_types=1);

namespace Src\Academic\AcademicCredential\Domain\Exceptions;

use DomainException;

final class DuplicateCredentialException extends DomainException
{
    public static function forTeacherSpecialtyDegree(): self
    {
        return new self('This teacher already has a credential with the same specialty and degree level.');
    }
}
