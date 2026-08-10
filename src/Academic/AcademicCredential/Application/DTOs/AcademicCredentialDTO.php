<?php

declare(strict_types=1);

namespace Src\Academic\AcademicCredential\Application\DTOs;

use Src\Academic\AcademicCredential\Domain\DegreeLevel;

final class AcademicCredentialDTO
{
    public function __construct(
        public readonly int $teacherId,
        public readonly int $specialtyId,
        public readonly DegreeLevel $degreeLevel,
        public readonly string $institution,
        public readonly int $yearObtained,
    ) {}
}
