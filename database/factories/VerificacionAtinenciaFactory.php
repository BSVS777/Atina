<?php

namespace Database\Factories;

use App\Models\AsignacionDocente;
use App\Models\VerificacionAtinencia;
use Atina\Docencia\Domain\Verificacion\ResultadoVerificacion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VerificacionAtinencia>
 */
class VerificacionAtinenciaFactory extends Factory
{
    protected $model = VerificacionAtinencia::class;

    public function definition(): array
    {
        return [
            'asignacion_docente_id' => AsignacionDocente::factory(),
            'catalogo_atinencia_id' => null,
            'user_id' => null,
            'resultado' => ResultadoVerificacion::Atinente,
            'es_provisional' => false,
            'justificacion' => null,
        ];
    }

    public function atinente(): static
    {
        return $this->state(fn () => ['resultado' => ResultadoVerificacion::Atinente]);
    }

    public function noAtinente(): static
    {
        return $this->state(fn () => ['resultado' => ResultadoVerificacion::NoAtinente]);
    }

    public function sinCatalogo(): static
    {
        return $this->state(fn () => [
            'resultado' => ResultadoVerificacion::SinCatalogo,
            'catalogo_atinencia_id' => null,
        ]);
    }
}
