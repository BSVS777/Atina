<?php

namespace Database\Factories;

use App\Models\Atestado;
use App\Models\Docente;
use App\Models\Especialidad;
use Atina\Docencia\Domain\Docente\GradoAcademico;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Atestado>
 */
class AtestadoFactory extends Factory
{
    protected $model = Atestado::class;

    public function definition(): array
    {
        return [
            'docente_id' => Docente::factory(),
            'especialidad_id' => Especialidad::query()->inRandomOrder()->value('id') ?? Especialidad::factory(),
            'grado' => fake()->randomElement(GradoAcademico::cases()),
            'institucion' => fake()->randomElement([
                'Universidad Técnica Nacional', 'Universidad de Costa Rica',
                'Instituto Tecnológico de Costa Rica', 'Universidad Nacional',
            ]),
            'anio_obtencion' => fake()->numberBetween(1995, 2024),
        ];
    }
}
