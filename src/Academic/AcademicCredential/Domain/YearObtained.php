<?php

declare(strict_types=1);

namespace Src\Academic\AcademicCredential\Domain;

use InvalidArgumentException;

/**
 * The year a credential was obtained cannot be future or implausible.
 */
final class YearObtained
{
    private const EARLIEST_PLAUSIBLE_YEAR = 1950;

    private int $value;

    public function __construct(int $value)
    {
        $currentYear = (int) date('Y');

        if ($value < self::EARLIEST_PLAUSIBLE_YEAR || $value > $currentYear) {
            throw new InvalidArgumentException(
                "Year obtained ({$value}) must be between " . self::EARLIEST_PLAUSIBLE_YEAR . " and {$currentYear}."
            );
        }

        $this->value = $value;
    }

    public function value(): int
    {
        return $this->value;
    }
}
