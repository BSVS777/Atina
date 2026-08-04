<?php

namespace Database\Factories;

use App\Models\Docente;
use App\Models\Puesto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Docente>
 */
class DocenteFactory extends Factory
{
    protected $model = Docente::class;

    public function definition(): array
    {
        return [
            'user_id' => null,
            'puesto_id' => Puesto::query()->inRandomOrder()->value('id') ?? Puesto::factory(),
            'cedula' => fake()->unique()->numerify('#-####-####'),
            'nombre' => fake()->firstName(),
            'primer_apellido' => fake()->lastName(),
            'segundo_apellido' => fake()->lastName(),
            'jornada_estimada' => fake()->randomElement(['0.25', '0.50', '0.75', '1.00']),
            'activo' => true,
        ];
    }
}
