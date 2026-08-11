<?php

declare(strict_types=1);

namespace Src\Academic\TeacherAssignment\Application\DTOs;

final class ProposeTeacherAssignmentDTO
{
    public function __construct(
        public readonly int $courseGroupId,
        public readonly int $teacherId,
        public readonly int $courseId,
        public readonly string $targetDate,
    ) {}
}
