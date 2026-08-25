<?php

namespace Tests\Unit\Academic\Fakes;

use Src\Academic\TeacherAssignment\Domain\Contracts\TeacherAssignmentRepositoryInterface;
use Src\Academic\TeacherAssignment\Domain\Entities\TeacherAssignment;

final class InMemoryTeacherAssignmentRepository implements TeacherAssignmentRepositoryInterface
{
    /** @var array<int, TeacherAssignment> */
    private array $assignments = [];

    private int $nextId = 1;

    public function find(int $id): ?TeacherAssignment
    {
        return $this->assignments[$id] ?? null;
    }

    public function all(): array
    {
        return array_values($this->assignments);
    }

    public function save(TeacherAssignment $assignment): TeacherAssignment
    {
        $id = $assignment->id() ?? $this->nextId++;
        $saved = $assignment->id() === null ? $assignment->withId($id) : $assignment;
        $this->assignments[$id] = $saved;

        return $saved;
    }

    public function delete(int $id): void
    {
        unset($this->assignments[$id]);
    }
}
