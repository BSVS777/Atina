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
            'docente_id' => Teacher::factory(),
            'especialidad_id' => Specialty::query()->inRandomOrder()->value('id') ?? Specialty::factory(),
            'grado' => fake()->randomElement(DegreeLevel::cases()),
            'institucion' => fake()->randomElement([
                'National Technical University', 'University of Costa Rica',
                'Costa Rica Institute of Technology', 'National University',
            ]),
            'anio_obtencion' => fake()->numberBetween(1995, 2024),
        ];
    }
}
