<?php

namespace Database\Factories;

use App\Models\AcademicCredential;
use App\Models\Specialty;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;
use Src\Academic\AcademicCredential\Domain\DegreeLevel;

/**
 * @extends Factory<AcademicCredential>
 */
class AcademicCredentialFactory extends Factory
{
    protected $model = AcademicCredential::class;

    public function definition(): array
    {
        return [
            'teacher_id' => Teacher::factory(),
            'specialty_id' => Specialty::query()->inRandomOrder()->value('id') ?? Specialty::factory(),
            'degree_level' => fake()->randomElement(DegreeLevel::cases()),
            'institution' => fake()->randomElement([
                'National Technical University', 'University of Costa Rica',
                'Costa Rica Institute of Technology', 'National University',
            ]),
            'year_obtained' => fake()->numberBetween(1995, 2024),
        ];
    }
}
