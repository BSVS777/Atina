<?php

namespace Database\Factories;

use App\Models\Position;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Teacher>
 */
class TeacherFactory extends Factory
{
    protected $model = Teacher::class;

    public function definition(): array
    {
        return [
            'user_id' => null,
            'position_id' => Position::query()->inRandomOrder()->value('id') ?? Position::factory(),
            'national_id' => fake()->unique()->numerify('#-####-####'),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'second_last_name' => fake()->lastName(),
            'estimated_workload' => fake()->randomElement(['0.25', '0.50', '0.75', '1.00']),
            'active' => true,
        ];
    }
}
