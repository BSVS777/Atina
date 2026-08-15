<?php

namespace Database\Factories;

use App\Models\CourseGroup;
use App\Models\Teacher;
use App\Models\TeacherAssignment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TeacherAssignment>
 */
class TeacherAssignmentFactory extends Factory
{
    protected $model = TeacherAssignment::class;

    public function definition(): array
    {
        return [
            'grupo_id' => CourseGroup::factory(),
            'docente_id' => Teacher::factory(),
            'jornada' => 1.00,
            'estado' => 'proposed',
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn () => ['estado' => 'confirmed']);
    }

    public function rejected(): static
    {
        return $this->state(fn () => ['estado' => 'rejected']);
    }
}
