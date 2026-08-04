<?php

namespace Database\Factories;

use App\Models\Curso;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

/**
 * Tabla de otro módulo (Oferta Académica) — factory mínima solo para poder
 * generar CatalogoAtinencia en tests/seeders de nuestro módulo.
 *
 * @extends Factory<Curso>
 */
class CursoFactory extends Factory
{
    protected $model = Curso::class;

    public function definition(): array
    {
        return [
            'carrera_id' => DB::table('carreras')->inRandomOrder()->value('id'),
            'codigo' => strtoupper(fake()->unique()->bothify('???-###')),
            'nombre' => fake()->sentence(3),
            'es_servicio' => false,
            'activo' => true,
        ];
    }
}
