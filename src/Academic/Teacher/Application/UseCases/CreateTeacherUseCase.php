<?php

declare(strict_types=1);

namespace Src\Academic\Teacher\Application\UseCases;

use App\Models\Teacher;
use Src\Academic\Teacher\Application\DTOs\TeacherDTO;

/**
 * Teacher has no Domain/Infrastructure layer — this screen was read-only
 * until now (see TeacherComponent's doc comment) and a repository/Entity
 * pair would add layers this single mutation doesn't need. Creates the
 * Eloquent record directly, DTO-typed like every other Academic create
 * flow's Presentation boundary.
 */
final class CreateTeacherUseCase
{
    public function handle(TeacherDTO $dto): Teacher
    {
        return Teacher::create([
            'position_id' => $dto->positionId,
            'national_id' => $dto->nationalId,
            'first_name' => $dto->firstName,
            'last_name' => $dto->lastName,
            'second_last_name' => $dto->secondLastName,
            'estimated_workload' => $dto->estimatedWorkload,
            'active' => $dto->active,
        ]);
    }
}
