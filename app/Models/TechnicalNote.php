<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Src\Academic\TeacherAssignment\Domain\TechnicalNoteStatus;
use Src\Academic\TeacherAssignment\Infrastructure\Persistence\Casts\TechnicalNoteStatusCast;

/**
 * Maps to the professor-provided `notas_tecnicas` table (institutional
 * schema, not owned by this project). See Docs/DIARIO_DECISIONES_IA.md.
 *
 * @property int $id
 * @property int $asignacion_docente_id
 * @property int $archivo_id
 * @property int|null $user_id
 * @property Carbon $fecha_limite_ratificacion
 * @property TechnicalNoteStatus $estado
 * @property Carbon|null $ratificada_at
 * @property-read TeacherAssignment $assignment
 * @property-read Archivo $archivo
 */
#[Fillable(['asignacion_docente_id', 'archivo_id', 'user_id', 'fecha_limite_ratificacion', 'estado', 'ratificada_at'])]
class TechnicalNote extends Model
{
    protected $table = 'notas_tecnicas';

    protected function casts(): array
    {
        return [
            'estado' => TechnicalNoteStatusCast::class,
            'fecha_limite_ratificacion' => 'date',
            'ratificada_at' => 'datetime',
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
     * @return BelongsTo<Archivo, $this>
     */
    public function archivo(): BelongsTo
    {
        return $this->belongsTo(Archivo::class);
    }
}
