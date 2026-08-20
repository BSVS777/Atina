<?php

declare(strict_types=1);

namespace Src\Academic\AffinityCatalog\Domain\Entities;

use DateTimeImmutable;
use InvalidArgumentException;

/**
 * One versioned entry of the affinity catalog for a course (DO-02).
 * Immutable *value object*: this class itself never mutates in place,
 * every setter-like operation returns a new instance (see `withId()`).
 * That is separate from whether the underlying persisted row can be
 * updated — CreateAffinityCatalogVersionUseCase always inserts a new
 * row, while UpdateAffinityCatalogVersionUseCase may update an existing
 * row in place, but only while `hasVerifications()` is still false.
 */
final class AffinityCatalogVersion
{
    /**
     * @param  array<int, int>  $specialtyIds  Ids of the specialties this version considers affine.
     */
    public function __construct(
        private readonly ?int $id,
        private readonly int $courseId,
        private readonly int $versionNumber,
        private readonly string $councilAgreement,
        private readonly string $gazetteNumber,
        private readonly DateTimeImmutable $effectiveStartDate,
        private readonly ?DateTimeImmutable $effectiveEndDate,
        private readonly array $specialtyIds,
    ) {
        if (trim($councilAgreement) === '') {
            throw new InvalidArgumentException('The council agreement (acuerdo) is required.');
        }

        if (trim($gazetteNumber) === '') {
            throw new InvalidArgumentException('The gazette number (número de Gaceta) is required.');
        }

        if ($effectiveEndDate !== null && $effectiveEndDate < $effectiveStartDate) {
            throw new InvalidArgumentException('The effective end date cannot be before the effective start date.');
        }

        if ($specialtyIds === []) {
            throw new InvalidArgumentException('At least one affine specialty is required.');
        }
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function courseId(): int
    {
        return $this->courseId;
    }

    public function versionNumber(): int
    {
        return $this->versionNumber;
    }

    public function councilAgreement(): string
    {
        return $this->councilAgreement;
    }

    public function gazetteNumber(): string
    {
        return $this->gazetteNumber;
    }

    public function effectiveStartDate(): DateTimeImmutable
    {
        return $this->effectiveStartDate;
    }

    public function effectiveEndDate(): ?DateTimeImmutable
    {
        return $this->effectiveEndDate;
    }

    /**
     * @return array<int, int>
     */
    public function specialtyIds(): array
    {
        return $this->specialtyIds;
    }

    public function coversDate(DateTimeImmutable $date): bool
    {
        if ($date < $this->effectiveStartDate) {
            return false;
        }

        return $this->effectiveEndDate === null || $date <= $this->effectiveEndDate;
    }

    public function overlapsRange(DateTimeImmutable $start, ?DateTimeImmutable $end): bool
    {
        $thisEnd = $this->effectiveEndDate ?? DateTimeImmutable::createFromFormat('Y-m-d', '9999-12-31');
        $otherEnd = $end ?? DateTimeImmutable::createFromFormat('Y-m-d', '9999-12-31');

        return $this->effectiveStartDate <= $otherEnd && $start <= $thisEnd;
    }

    public function isAffineToSpecialty(int $specialtyId): bool
    {
        return in_array($specialtyId, $this->specialtyIds, true);
    }

    public function withId(int $id): self
    {
        return new self(
            $id,
            $this->courseId,
            $this->versionNumber,
            $this->councilAgreement,
            $this->gazetteNumber,
            $this->effectiveStartDate,
            $this->effectiveEndDate,
            $this->specialtyIds,
        );
    }
}
