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
        collect(['Professor 2', 'Professor 3', 'Professor 4', 'Specialist Professor 1'])
            ->each(fn (string $name) => Position::query()->firstOrCreate(['name' => $name]));

        collect([
            'Information Systems Engineering',
            'Computer Engineering',
            'Business Administration',
            'Industrial Engineering',
            'Public Accounting',
            'Education Sciences with English Emphasis',
            'Environmental Engineering',
            'Food Engineering',
        ])->each(fn (string $name) => Specialty::query()->firstOrCreate(['name' => $name]));

        Teacher::factory()
            ->count(8)
            ->create()
            ->each(function (Teacher $teacher) {
                AcademicCredential::factory()->create([
                    'teacher_id' => $teacher->id,
                    'degree_level' => fake()->randomElement([DegreeLevel::Bachelor, DegreeLevel::Master]),
                ]);
            });
    }
}
