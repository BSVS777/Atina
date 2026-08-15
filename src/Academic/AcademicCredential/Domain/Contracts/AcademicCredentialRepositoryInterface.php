<?php

declare(strict_types=1);

namespace Src\Academic\AcademicCredential\Domain\Contracts;

use Src\Academic\AcademicCredential\Domain\DegreeLevel;
use Src\Academic\AcademicCredential\Domain\Entities\AcademicCredential;

interface AcademicCredentialRepositoryInterface
{
    public function find(int $id): ?AcademicCredential;

    /**
     * @return array<int, AcademicCredential>
     */
    public function forTeacher(int $teacherId): array;

    /**
     * Mirrors the academic_credentials_teacher_specialty_degree_unique
     * constraint: a teacher can't have two credentials with the same
     * specialty + degree combination.
     */
    public function existsForTeacherSpecialtyDegree(
        int $teacherId,
        int $specialtyId,
        DegreeLevel $degreeLevel,
        ?int $exceptCredentialId = null,
    ): bool;

    public function save(AcademicCredential $credential): AcademicCredential;
}
