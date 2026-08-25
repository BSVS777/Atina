<?php

namespace Tests\Unit\Academic;

use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Src\Academic\AcademicCredential\Domain\StudyPeriod;

class StudyPeriodTest extends TestCase
{
    public function test_accepts_a_plausible_past_period(): void
    {
        $period = new StudyPeriod(
            new DateTimeImmutable('2005-03-01'),
            new DateTimeImmutable('2010-11-30'),
        );

        $this->assertSame('2005-03-01', $period->startDate()->format('Y-m-d'));
        $this->assertSame('2010-11-30', $period->endDate()->format('Y-m-d'));
        $this->assertSame(2010, $period->yearObtained());
    }

    public function test_rejects_a_start_date_before_1950(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new StudyPeriod(
            new DateTimeImmutable('1949-12-31'),
            new DateTimeImmutable('1995-01-01'),
        );
    }

    public function test_rejects_a_future_end_date(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new StudyPeriod(
            new DateTimeImmutable('2020-01-01'),
            new DateTimeImmutable('+1 year'),
        );
    }

    public function test_rejects_an_end_date_before_the_start_date(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new StudyPeriod(
            new DateTimeImmutable('2015-01-01'),
            new DateTimeImmutable('2010-01-01'),
        );
    }

    public function test_accepts_the_earliest_plausible_start_date(): void
    {
        $period = new StudyPeriod(
            new DateTimeImmutable('1950-01-01'),
            new DateTimeImmutable('1955-01-01'),
        );

        $this->assertSame('1950-01-01', $period->startDate()->format('Y-m-d'));
    }

    public function test_accepts_the_same_day_as_start_and_end(): void
    {
        $period = new StudyPeriod(
            new DateTimeImmutable('2020-01-01'),
            new DateTimeImmutable('2020-01-01'),
        );

        $this->assertSame($period->startDate()->format('Y-m-d'), $period->endDate()->format('Y-m-d'));
    }

    public function test_accepts_today_as_the_end_date(): void
    {
        $today = new DateTimeImmutable('today');

        $period = new StudyPeriod(new DateTimeImmutable('2020-01-01'), $today);

        $this->assertSame($today->format('Y-m-d'), $period->endDate()->format('Y-m-d'));
    }
}
