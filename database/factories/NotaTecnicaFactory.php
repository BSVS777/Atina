<?php

namespace Database\Factories;

use App\Models\Archivo;
use App\Models\AsignacionDocente;
use App\Models\NotaTecnica;
use Atina\Docencia\Domain\NotaTecnica\EstadoNotaTecnica;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NotaTecnica>
 */
class NotaTecnicaFactory extends Factory
{
    protected $model = NotaTecnica::class;

    public function definition(): array
    {
        return [
            'asignacion_docente_id' => AsignacionDocente::factory(),
            'archivo_id' => Archivo::factory(),
            'user_id' => null,
            'fecha_limite_ratificacion' => fake()->dateTimeBetween('now', '+30 days'),
            'estado' => EstadoNotaTecnica::RatificacionPendiente,
            'ratificada_at' => null,
        ];
    }

    public function ratificada(): static
    {
        return $this->state(fn () => [
            'estado' => EstadoNotaTecnica::Ratificada,
            'ratificada_at' => now(),
        ]);
    }

    public function vencida(): static
    {
        return $this->state(fn () => [
            'estado' => EstadoNotaTecnica::Vencida,
            'fecha_limite_ratificacion' => fake()->dateTimeBetween('-30 days', '-1 day'),
        ]);
    }
}
