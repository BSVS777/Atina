<?php

namespace App\Models;

use Database\Factories\TeacherAssignmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Src\Academic\TeacherAssignment\Domain\ProposalStatus;
use Src\Academic\TeacherAssignment\Infrastructure\Persistence\Casts\ProposalStatusCast;

/**
 * Maps to the professor-provided `asignaciones_docentes` table
 * (institutional schema, not owned by this project). Only this model
 * and EloquentTeacherAssignmentRepository know the Spanish column
 * names — see Docs/DIARIO_DECISIONES_IA.md.
 *
 * @property int $id
 * @property int $grupo_id
 * @property int $docente_id
 * @property ProposalStatus $estado
 * @property-read CourseGroup $group
 * @property-read Teacher $teacher
 * @property-read Collection<int, AffinityVerification> $verifications
 * @property-read TechnicalNote|null $technicalNote
 */
#[Fillable(['grupo_id', 'docente_id', 'estado'])]
class TeacherAssignment extends Model
{
    /** @use HasFactory<TeacherAssignmentFactory> */
    use HasFactory;

    protected $table = 'asignaciones_docentes';

    protected function casts(): array
    {
        return [
            'estado' => ProposalStatusCast::class,
        ];
    }

    /**
     * @return BelongsTo<CourseGroup, $this>
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(CourseGroup::class, 'grupo_id');
    }

    /**
     * @return BelongsTo<Teacher, $this>
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'docente_id');
    }

    /**
     * @return HasMany<AffinityVerification, $this>
     */
    public function verifications(): HasMany
    {
        return $this->hasMany(AffinityVerification::class, 'asignacion_docente_id');
    }

    /**
     * @return HasOne<TechnicalNote, $this>
     */
    public function technicalNote(): HasOne
    {
        return $this->hasOne(TechnicalNote::class, 'asignacion_docente_id');
    }
}
