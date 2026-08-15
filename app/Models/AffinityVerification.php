<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Src\Academic\TeacherAssignment\Domain\VerificationResult;
use Src\Academic\TeacherAssignment\Infrastructure\Persistence\Casts\VerificationResultCast;

/**
 * Maps to the professor-provided `verificaciones_atinencia` table
 * (institutional schema, not owned by this project) — an append-only
 * trail, never updated after creation. See Docs/DIARIO_DECISIONES_IA.md.
 *
 * @property int $id
 * @property int $asignacion_docente_id
 * @property int|null $catalogo_atinencia_id
 * @property int|null $atestado_id
 * @property int|null $user_id
 * @property VerificationResult $resultado
 * @property bool $es_provisional
 * @property string|null $justificacion
 * @property-read TeacherAssignment $assignment
 * @property-read AffinityCatalogVersion|null $catalogVersion
 * @property-read AcademicCredential|null $matchedCredential
 */
#[Fillable(['asignacion_docente_id', 'catalogo_atinencia_id', 'atestado_id', 'user_id', 'resultado', 'es_provisional', 'justificacion'])]
class AffinityVerification extends Model
{
    protected $table = 'verificaciones_atinencia';

    const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'resultado' => VerificationResultCast::class,
            'es_provisional' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<TeacherAssignment, $this>
     */
    public function assignment(): BelongsTo
    {
        return $this->belongsTo(TeacherAssignment::class, 'asignacion_docente_id');
    }

    /**
     * @return BelongsTo<AffinityCatalogVersion, $this>
     */
    public function catalogVersion(): BelongsTo
    {
        return $this->belongsTo(AffinityCatalogVersion::class, 'catalogo_atinencia_id');
    }

    /**
     * @return BelongsTo<AcademicCredential, $this>
     */
    public function matchedCredential(): BelongsTo
    {
        return $this->belongsTo(AcademicCredential::class, 'atestado_id');
    }
}
