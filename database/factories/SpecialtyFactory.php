<?php

namespace Database\Factories;

use App\Models\Specialty;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Specialty>
 */
class SpecialtyFactory extends Factory
{
    protected $model = Specialty::class;

    public function definition(): array
    {
        // Small, UNIQUE-constrained vocabulary — callers should prefer
        // Specialty::inRandomOrder()->value('id') and only create when none exist.
        return [
            'name' => fake()->unique()->randomElement([
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
