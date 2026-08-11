<?php

namespace Database\Factories;

use App\Models\Career;
use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    protected $model = Course::class;

    public function definition(): array
    {
        return [
            'career_id' => Career::query()->inRandomOrder()->value('id') ?? Career::factory(),
            'code' => fake()->unique()->regexify('[A-Z]{2,4}-[0-9]{3}'),
            'name' => fake()->randomElement([
                'Programación en Ambiente Web I',
                'Programación en Ambiente Web II',
                'Estructuras de Datos',
                'Base de Datos I',
                'Redes de Computadoras',
                'Ingeniería de Software',
            ]),
            'active' => true,
        ];
    }
}
