<?php

declare(strict_types=1);

namespace Src\Academic\AcademicCredential\Domain\Entities;

use InvalidArgumentException;
use Src\Academic\AcademicCredential\Domain\DegreeLevel;
use Src\Academic\AcademicCredential\Domain\StudyPeriod;

/**
 * A teacher's academic credential (degree, institution, study period,
 * specialty). Specialty is referenced by id only — the domain doesn't need
 * the specialty's name for any invariant, and validating that the id
 * exists is the repository's job, not the entity's.
 */
final class AcademicCredential
{
    public function __construct(
        private readonly ?int $id,
        private readonly int $teacherId,
        private readonly int $specialtyId,
        private readonly DegreeLevel $degreeLevel,
        private readonly string $institution,
        private readonly StudyPeriod $studyPeriod,
    ) {
        if (trim($institution) === '') {
            throw new InvalidArgumentException('Institution is required.');
        }
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function teacherId(): int
    {
        return $this->teacherId;
    }

    public function specialtyId(): int
    {
        return $this->specialtyId;
    }

    public function degreeLevel(): DegreeLevel
    {
        return $this->degreeLevel;
    }

    public function institution(): string
    {
        return $this->institution;
    }

    public function studyPeriod(): StudyPeriod
    {
        return $this->studyPeriod;
    }

    public function withId(int $id): self
    {
        return new self($id, $this->teacherId, $this->specialtyId, $this->degreeLevel, $this->institution, $this->studyPeriod);
    }
}
