<?php

declare(strict_types=1);

namespace Src\Academic\Teacher\Application\DTOs;

final class TeacherDTO
{
    public function __construct(
        public readonly int $positionId,
        public readonly string $nationalId,
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly ?string $secondLastName,
        public readonly ?string $estimatedWorkload,
        public readonly bool $active,
    ) {}
}
