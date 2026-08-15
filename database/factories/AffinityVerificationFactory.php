<?php

namespace Database\Factories;

use App\Models\AffinityVerification;
use App\Models\TeacherAssignment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Src\Academic\TeacherAssignment\Domain\VerificationResult;

/**
 * @extends Factory<AffinityVerification>
 */
class AffinityVerificationFactory extends Factory
{
    protected $model = AffinityVerification::class;

    public function definition(): array
    {
        return [
            'asignacion_docente_id' => TeacherAssignment::factory(),
            'catalogo_atinencia_id' => null,
            'atestado_id' => null,
            'user_id' => null,
            'resultado' => VerificationResult::Matched,
            'es_provisional' => false,
            'justificacion' => null,
        ];
    }

    public function matched(): static
    {
        return $this->state(fn () => ['resultado' => VerificationResult::Matched]);
    }

    public function notMatched(): static
    {
        return $this->state(fn () => ['resultado' => VerificationResult::NotMatched, 'atestado_id' => null]);
    }

    public function noCatalog(): static
    {
        return $this->state(fn () => ['resultado' => VerificationResult::NoCatalog, 'catalogo_atinencia_id' => null, 'atestado_id' => null]);
    }
}
