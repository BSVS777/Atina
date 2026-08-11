<?php

namespace Database\Factories;

use App\Models\Specialty;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Specialty>
 */
class SpecialtyFactory extends Factory
{
    protected $model = Specialty::class;

    public function definition(): array
    {
        // Small, UNIQUE-constrained vocabulary — callers should prefer
        // Specialty::inRandomOrder()->value('id') and only create when none exist.
        return [
            'name' => fake()->unique()->randomElement([
                'Information Systems Engineering',
                'Computer Engineering',
                'Business Administration',
                'Industrial Engineering',
                'Public Accounting',
                'Education Sciences with English Emphasis',
                'Environmental Engineering',
                'Food Engineering',
            ]),
        ];
    }
}
