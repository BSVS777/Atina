<?php

declare(strict_types=1);

namespace Src\Academic\AcademicCredential\Domain;

use DateTimeImmutable;
use InvalidArgumentException;

/**
 * The start and end date of the studies that led to a credential.
 * Replaces the former single "year obtained" figure: the end date is
 * treated as the date the credential/title was obtained.
 */
final class StudyPeriod
{
    private const EARLIEST_PLAUSIBLE_YEAR = 1950;

    public function __construct(
        private readonly DateTimeImmutable $startDate,
        private readonly DateTimeImmutable $endDate,
    ) {
        $earliestPlausible = new DateTimeImmutable(self::EARLIEST_PLAUSIBLE_YEAR.'-01-01');
        $today = new DateTimeImmutable('today');

        if ($this->startDate < $earliestPlausible) {
            throw new InvalidArgumentException(
                'Start date cannot be earlier than '.self::EARLIEST_PLAUSIBLE_YEAR.'.'
            );
        }

        if ($this->endDate > $today) {
            throw new InvalidArgumentException('End date cannot be in the future.');
        }

        if ($this->endDate < $this->startDate) {
            throw new InvalidArgumentException('End date cannot be earlier than the start date.');
        }
    }

    public function startDate(): DateTimeImmutable
    {
        return $this->startDate;
    }

    public function endDate(): DateTimeImmutable
    {
        return $this->endDate;
    }

    /**
     * The year the credential was obtained, derived from the end date.
     * Kept for anything that still needs a single representative year
     * (e.g. sorting, display fallback).
     */
    public function yearObtained(): int
    {
        return (int) $this->endDate->format('Y');
    }
}
