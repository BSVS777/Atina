<?php

namespace Database\Factories;

use App\Models\CatalogoAtinencia;
use App\Models\Curso;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CatalogoAtinencia>
 */
class CatalogoAtinenciaFactory extends Factory
{
    protected $model = CatalogoAtinencia::class;

    public function definition(): array
    {
        return [
            'curso_id' => Curso::factory(),
            'version' => 1,
            'acuerdo' => 'Acuerdo Consejo Universitario '.fake()->numberBetween(1, 50).'-'.fake()->year(),
            'numero_gaceta' => (string) fake()->numberBetween(1, 300),
            'vigencia_inicio' => fake()->dateTimeBetween('-3 years', '-1 month'),
            'vigencia_fin' => null,
        ];
    }
}
