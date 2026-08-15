<?php

namespace Tests\Unit\Academic\Fakes;

use Src\Academic\AcademicCredential\Domain\Contracts\AcademicCredentialRepositoryInterface;
use Src\Academic\AcademicCredential\Domain\DegreeLevel;
use Src\Academic\AcademicCredential\Domain\Entities\AcademicCredential;

final class InMemoryAcademicCredentialRepository implements AcademicCredentialRepositoryInterface
{
    /** @var array<int, AcademicCredential> */
    private array $credentials = [];

    private int $nextId = 1;

    public function find(int $id): ?AcademicCredential
    {
        return $this->credentials[$id] ?? null;
    }

    public function forTeacher(int $teacherId): array
    {
        return array_values(array_filter(
            $this->credentials,
            fn (AcademicCredential $credential) => $credential->teacherId() === $teacherId,
        ));
    }

    public function existsForTeacherSpecialtyDegree(
        int $teacherId,
        int $specialtyId,
        DegreeLevel $degreeLevel,
        ?int $exceptCredentialId = null,
    ): bool {
        foreach ($this->credentials as $credential) {
            if ($credential->id() === $exceptCredentialId) {
                continue;
            }

            if ($credential->teacherId() === $teacherId
                && $credential->specialtyId() === $specialtyId
                && $credential->degreeLevel() === $degreeLevel) {
                return true;
            }
        }

        return false;
    }

    public function save(AcademicCredential $credential): AcademicCredential
    {
        $id = $credential->id() ?? $this->nextId++;
        $saved = $credential->id() === null ? $credential->withId($id) : $credential;
        $this->credentials[$id] = $saved;

        return $saved;
    }
}
