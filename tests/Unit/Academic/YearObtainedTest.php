<?php

namespace Tests\Unit\Academic;

use InvalidArgumentException;
use Src\Academic\AcademicCredential\Domain\YearObtained;
use Tests\TestCase;

class YearObtainedTest extends TestCase
{
    public function test_accepts_a_plausible_past_year(): void
    {
        $year = new YearObtained(2010);

        $this->assertSame(2010, $year->value());
    }

    public function test_rejects_a_year_before_1950(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new YearObtained(1949);
    }

    public function test_rejects_a_future_year(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new YearObtained((int) date('Y') + 1);
    }

    public function test_accepts_the_current_year(): void
    {
        $currentYear = (int) date('Y');

        $year = new YearObtained($currentYear);

        $this->assertSame($currentYear, $year->value());
    }
}
