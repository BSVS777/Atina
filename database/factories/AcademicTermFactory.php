<?php

namespace Database\Factories;

use App\Models\AcademicTerm;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<AcademicTerm>
 */
class AcademicTermFactory extends Factory
{
    protected $model = AcademicTerm::class;

    public function definition(): array
    {
        $year = fake()->numberBetween(2024, 2026);
        $termNumber = fake()->numberBetween(1, 3);
        $startDate = $this->startDateFor($year, $termNumber);

        return [
            'year' => $year,
            'term_number' => $termNumber,
            'start_date' => $startDate,
            'end_date' => $startDate->copy()->addMonths(4)->subDay(),
        ];
    }

    private function startDateFor(int $year, int $termNumber): Carbon
    {
        return match ($termNumber) {
            1 => Carbon::create($year, 1, 15),
            2 => Carbon::create($year, 5, 1),
            default => Carbon::create($year, 9, 1),
        };
    }
}
