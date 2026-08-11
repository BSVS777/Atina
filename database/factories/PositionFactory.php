<?php

namespace Database\Factories;

use App\Models\Position;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Position>
 */
class PositionFactory extends Factory
{
    protected $model = Position::class;

    public function definition(): array
    {
        // Small, UNIQUE-constrained vocabulary — callers should prefer
        // Position::inRandomOrder()->value('id') and only create when none exist.
        return [
            'name' => fake()->unique()->randomElement([
                'Professor 2', 'Professor 3', 'Professor 4', 'Specialist Professor 1',
            ]),
        ];
    }
}
