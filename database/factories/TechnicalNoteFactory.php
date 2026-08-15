<?php

namespace Database\Factories;

use App\Models\Archivo;
use App\Models\TeacherAssignment;
use App\Models\TechnicalNote;
use Illuminate\Database\Eloquent\Factories\Factory;
use Src\Academic\TeacherAssignment\Domain\TechnicalNoteStatus;

/**
 * @extends Factory<TechnicalNote>
 */
class TechnicalNoteFactory extends Factory
{
    protected $model = TechnicalNote::class;

    public function definition(): array
    {
        return [
            'asignacion_docente_id' => TeacherAssignment::factory(),
            'archivo_id' => Archivo::factory(),
            'user_id' => null,
            'fecha_limite_ratificacion' => fake()->dateTimeBetween('now', '+30 days')->format('Y-m-d'),
            'estado' => TechnicalNoteStatus::PendingRatification,
        ];
    }

    public function ratified(): static
    {
        return $this->state(fn () => [
            'estado' => TechnicalNoteStatus::Ratified,
            'ratificada_at' => now(),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'estado' => TechnicalNoteStatus::Expired,
            'fecha_limite_ratificacion' => fake()->dateTimeBetween('-30 days', '-1 day')->format('Y-m-d'),
        ]);
    }
}
