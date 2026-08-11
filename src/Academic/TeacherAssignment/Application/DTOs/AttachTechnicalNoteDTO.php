<?php

declare(strict_types=1);

namespace Src\Academic\TeacherAssignment\Application\DTOs;

use Src\Academic\TeacherAssignment\Domain\Contracts\UploadedDocument;

final class AttachTechnicalNoteDTO
{
    public function __construct(
        public readonly int $teacherAssignmentId,
        public readonly string $ratificationDeadline,
        public readonly UploadedDocument $document,
    ) {}
}
