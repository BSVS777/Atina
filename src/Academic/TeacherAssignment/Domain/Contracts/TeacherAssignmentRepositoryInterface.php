<?php

declare(strict_types=1);

namespace Src\Academic\TeacherAssignment\Domain\Contracts;

use Src\Academic\TeacherAssignment\Domain\Entities\TeacherAssignment;

interface TeacherAssignmentRepositoryInterface
{
    public function find(int $id): ?TeacherAssignment;

    /**
     * @return array<int, TeacherAssignment>
     */
    public function all(): array;

    public function save(TeacherAssignment $assignment): TeacherAssignment;
}
