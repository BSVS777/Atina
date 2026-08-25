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
        $endDate = fake()->dateTimeBetween('1995-01-01', 'now');
        $startDate = (clone $endDate)->modify('-'.fake()->numberBetween(3, 6).' years');

        return [
            'docente_id' => Teacher::factory(),
            'especialidad_id' => Specialty::query()->inRandomOrder()->value('id') ?? Specialty::factory(),
            'grado' => fake()->randomElement(DegreeLevel::cases()),
            'institucion' => fake()->randomElement([
                'Universidad Técnica Nacional', 'Universidad de Costa Rica',
                'Instituto Tecnológico de Costa Rica', 'Universidad Nacional',
            ]),
            'fecha_inicio' => $startDate->format('Y-m-d'),
            'fecha_fin' => $endDate->format('Y-m-d'),
        ];
    }
}
