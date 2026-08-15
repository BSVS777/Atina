<?php

declare(strict_types=1);

namespace Src\Academic\TeacherAssignment\Domain\Contracts;

use Src\Academic\TeacherAssignment\Domain\Entities\TechnicalNote;

interface TechnicalNoteRepositoryInterface
{
    public function find(int $id): ?TechnicalNote;

    public function forAssignment(int $teacherAssignmentId): ?TechnicalNote;

    /**
     * @return array<int, TechnicalNote>
     */
    public function pendingRatification(): array;

    public function save(TechnicalNote $note, ?UploadedDocument $document = null): TechnicalNote;
}
