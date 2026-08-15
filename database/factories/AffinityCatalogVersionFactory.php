<?php

namespace Database\Factories;

use App\Models\AffinityCatalogVersion;
use App\Models\Course;
use App\Models\Specialty;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AffinityCatalogVersion>
 */
class AffinityCatalogVersionFactory extends Factory
{
    protected $model = AffinityCatalogVersion::class;

    public function definition(): array
    {
        return [
            'curso_id' => Course::factory(),
            'version' => 1,
            'acuerdo' => 'Acuerdo Consejo Universitario '.fake()->numberBetween(1, 50).'-'.fake()->year(),
            'numero_gaceta' => (string) fake()->numberBetween(1, 300),
            'vigencia_inicio' => fake()->dateTimeBetween('-3 years', '-1 month')->format('Y-m-d'),
            'vigencia_fin' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (AffinityCatalogVersion $version) {
            if ($version->especialidadesAtinentes()->exists()) {
                return;
            }

            $specialtyId = Specialty::query()->inRandomOrder()->value('id') ?? Specialty::factory()->create()->id;
            $version->especialidadesAtinentes()->sync([$specialtyId]);
        });
    }
}
