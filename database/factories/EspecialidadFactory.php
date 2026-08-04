<?php

namespace Database\Factories;

use App\Models\Especialidad;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Especialidad>
 */
class EspecialidadFactory extends Factory
{
    protected $model = Especialidad::class;

    public function definition(): array
    {
        // Vocabulario chico y con UNIQUE en BD — no usar fake()->unique(),
        // se agota rápido. Quien consuma esta factory debe preferir
        // Especialidad::inRandomOrder()->value('id') y solo crear si no hay ninguna.
        return [
            'nombre' => fake()->randomElement([
                'Ingeniería en Sistemas de Información',
                'Ingeniería en Computación',
                'Administración de Empresas',
                'Ingeniería Industrial',
                'Contaduría Pública',
                'Ciencias de la Educación con énfasis en Inglés',
                'Ingeniería Ambiental',
                'Ingeniería en Alimentos',
            ]),
        ];
    }
}
