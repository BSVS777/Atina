<?php

namespace Database\Factories;

use App\Models\Puesto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Puesto>
 */
class PuestoFactory extends Factory
{
    protected $model = Puesto::class;

    public function definition(): array
    {
        // Vocabulario chico y con UNIQUE en BD — no usar fake()->unique(),
        // se agota rápido. Quien consuma esta factory debe preferir
        // Puesto::inRandomOrder()->value('id') y solo crear si no hay ninguno.
        return [
            'nombre' => fake()->randomElement([
                'Profesor 2', 'Profesor 3', 'Profesor 4', 'Profesor Especialista 1',
            ]),
        ];
    }
}
