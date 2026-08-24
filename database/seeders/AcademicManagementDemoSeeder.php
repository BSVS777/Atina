<?php

namespace Database\Seeders;

use App\Models\AcademicCredential;
use App\Models\Position;
use App\Models\Specialty;
use App\Models\Teacher;
use Illuminate\Database\Seeder;
use Src\Academic\AcademicCredential\Domain\DegreeLevel;

/**
 * Development-only demo data for the Academic module: a handful of
 * positions, specialties, teachers and academic credentials so there is
 * something real on screen while the module is exercised.
 */
class AcademicManagementDemoSeeder extends Seeder
{
    public function run(): void
    {
        collect(['Profesor 2', 'Profesor 3', 'Profesor 4', 'Profesor Especialista 1'])
            ->each(fn (string $name) => Position::query()->firstOrCreate(['nombre' => $name]));

        collect([
            'Ingeniería en Sistemas de Información',
            'Ingeniería en Computación',
            'Administración de Empresas',
            'Ingeniería Industrial',
            'Contaduría Pública',
            'Ciencias de la Educación con énfasis en Inglés',
            'Ingeniería Ambiental',
            'Ingeniería en Alimentos',
        ])->each(fn (string $name) => Specialty::query()->firstOrCreate(['nombre' => $name]));

        if (Teacher::query()->count() > 0) {
            return;
        }

        Teacher::factory()
            ->count(8)
            ->create()
            ->each(function (Teacher $teacher) {
                AcademicCredential::factory()->create([
                    'docente_id' => $teacher->id,
                    'grado' => fake()->randomElement([DegreeLevel::Bachelor, DegreeLevel::Master]),
                ]);
            });
    }
}
