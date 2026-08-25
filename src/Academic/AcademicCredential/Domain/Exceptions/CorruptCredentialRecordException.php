<?php

declare(strict_types=1);

namespace Src\Academic\AcademicCredential\Domain\Exceptions;

use DomainException;

final class CorruptCredentialRecordException extends DomainException
{
    public static function missingStudyPeriod(int $id): self
    {
        return new self(
            "Academic credential {$id} is missing required study-period dates. ".
            'The atestados table should never allow this (fecha_inicio/fecha_fin are NOT NULL); '.
            'check for pending migrations or a schema mismatch on this environment.'
        );
    }
}
