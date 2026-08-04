<?php

namespace Database\Factories;

use App\Models\AsignacionDocente;
use App\Models\Curso;
use App\Models\Docente;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

/**
 * Tabla de otro módulo (Oferta Académica) — factory mínima solo para poder
 * generar VerificacionAtinencia y NotaTecnica en nuestro módulo. `grupo_id`
 * se resuelve con un insert directo (no hay Model propio para `grupos`)
 * reusando periodo/meta/modalidad ya sembrados por database/sql/seed_compartido.sql.
 *
 * @extends Factory<AsignacionDocente>
 */
class AsignacionDocenteFactory extends Factory
{
    protected $model = AsignacionDocente::class;

    public function definition(): array
    {
        return [
            'grupo_id' => $this->grupoIdMinimo(),
            'docente_id' => Docente::factory(),
            'jornada' => fake()->randomElement(['0.25', '0.50', '0.75', '1.00']),
            'condicion_nombramiento' => 'Interino',
            'estado' => 'Propuesta',
        ];
    }

    private function grupoIdMinimo(): int
    {
        return DB::table('grupos')->insertGetId([
            'curso_id' => Curso::factory()->create()->id,
            'periodo_academico_id' => DB::table('periodos_academicos')->inRandomOrder()->value('id'),
            'meta_id' => DB::table('metas')->inRandomOrder()->value('id'),
            'modalidad_id' => DB::table('modalidades')->inRandomOrder()->value('id'),
            'numero' => 1,
            'cupo' => fake()->numberBetween(15, 40),
            'estado' => 'Borrador',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
