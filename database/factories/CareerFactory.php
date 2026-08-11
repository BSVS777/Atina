<?php

namespace Database\Factories;

use App\Models\Career;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Career>
 */
class CareerFactory extends Factory
{
    protected $model = Career::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->randomElement([
                'Administración y Gestión de Recursos Humanos',
                'Administración Aduanera',
                'Ingeniería en Tecnologías de Información - Tecnologías de Información',
                'Ingeniería del Software - Tecnologías Informáticas',
                'Contabilidad y Finanzas - Contaduría Pública',
                'Asistencia Administrativa',
                'Inglés como Lengua Extranjera',
                'Administración Agroindustrial',
                'Gestión de Centros de Servicios Compartidos',
                'Ingeniería en Gestión Ambiental',
                'Administración del Comercio Exterior',
            ]),
            'active' => true,
        ];
    }
}
