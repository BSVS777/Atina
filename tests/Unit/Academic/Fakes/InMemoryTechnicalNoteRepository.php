<?php

namespace Tests\Unit\Academic\Fakes;

use Src\Academic\TeacherAssignment\Domain\Contracts\TechnicalNoteRepositoryInterface;
use Src\Academic\TeacherAssignment\Domain\Contracts\UploadedDocument;
use Src\Academic\TeacherAssignment\Domain\Entities\TechnicalNote;
use Src\Academic\TeacherAssignment\Domain\TechnicalNoteStatus;

final class InMemoryTechnicalNoteRepository implements TechnicalNoteRepositoryInterface
{
    /** @var array<int, TechnicalNote> */
    private array $notes = [];

    private int $nextId = 1;

    /** @var list<UploadedDocument> */
    public array $storedDocuments = [];

    public function find(int $id): ?TechnicalNote
    {
        return $this->notes[$id] ?? null;
    }

    public function forAssignment(int $teacherAssignmentId): ?TechnicalNote
    {
        foreach ($this->notes as $note) {
            if ($note->teacherAssignmentId() === $teacherAssignmentId) {
                return $note;
            }
        }

        return null;
    }

    public function pendingRatification(): array
    {
        return array_values(array_filter(
            $this->notes,
            fn (TechnicalNote $note) => $note->status() === TechnicalNoteStatus::PendingRatification,
        ));
    }

    public function save(TechnicalNote $note, ?UploadedDocument $document = null): TechnicalNote
    {
        if ($document !== null) {
            $this->storedDocuments[] = $document;
        }

        $id = $note->id() ?? $this->nextId++;
        $saved = $note->id() === null ? $note->withId($id) : $note;
        $this->notes[$id] = $saved;

        return $saved;
    }
}
