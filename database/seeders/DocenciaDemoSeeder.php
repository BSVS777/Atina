<?php

namespace Database\Seeders;

use App\Models\Atestado;
use App\Models\Docente;
use App\Models\Especialidad;
use App\Models\Puesto;
use Atina\Docencia\Domain\Docente\GradoAcademico;
use Illuminate\Database\Seeder;

/**
 * Datos de desarrollo (no producción) para el módulo Gestión Docente: unos
 * puestos, especialidades, docentes y atestados de ejemplo para poder ver
 * algo real en pantalla mientras se construye DO-01.
 */
class DocenciaDemoSeeder extends Seeder
{
    public function run(): void
    {
        collect(['Profesor 2', 'Profesor 3', 'Profesor 4', 'Profesor Especialista 1'])
            ->each(fn (string $nombre) => Puesto::firstOrCreate(['nombre' => $nombre]));

        collect([
            'Ingeniería en Sistemas de Información',
            'Ingeniería en Computación',
            'Administración de Empresas',
            'Ingeniería Industrial',
            'Contaduría Pública',
            'Ciencias de la Educación con énfasis en Inglés',
            'Ingeniería Ambiental',
            'Ingeniería en Alimentos',
        ])->each(fn (string $nombre) => Especialidad::firstOrCreate(['nombre' => $nombre]));

        Docente::factory()
            ->count(8)
            ->create()
            ->each(function (Docente $docente) {
                Atestado::factory()->create([
                    'docente_id' => $docente->id,
                    'grado' => fake()->randomElement([GradoAcademico::Licenciatura, GradoAcademico::Maestria]),
                ]);
            });
    }
}
