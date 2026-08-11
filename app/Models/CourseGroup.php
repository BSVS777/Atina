<?php

namespace App\Models;

use Database\Factories\CourseGroupFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Maps to the professor-provided `grupos` table (institutional schema,
 * not owned by this project) — a course section for a given academic
 * term. `meta_id`/`modalidad_id`/`cupo` are mandatory on the real table
 * (room/scheduling concerns owned by another module) but carry no
 * business meaning here; they get a fixed bootstrap value at creation
 * time and are never exposed as English attributes. See
 * Docs/DIARIO_DECISIONES_IA.md.
 *
 * @property int $id
 * @property int $course_id
 * @property int $academic_term_id
 * @property int $section_number
 * @property-read Course $course
 * @property-read AcademicTerm $academicTerm
 */
#[Fillable(['course_id', 'academic_term_id', 'section_number', 'meta_id', 'modalidad_id', 'cupo'])]
class CourseGroup extends Model
{
    /** @use HasFactory<CourseGroupFactory> */
    use HasFactory;

    protected $table = 'grupos';

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'curso_id');
    }

    /**
     * @return BelongsTo<AcademicTerm, $this>
     */
    public function academicTerm(): BelongsTo
    {
        return $this->belongsTo(AcademicTerm::class, 'periodo_academico_id');
    }

    public function label(): string
    {
        return "{$this->course->code} — {$this->course->name} (§{$this->section_number}, {$this->academicTerm->label()})";
    }

    /**
     * @return Attribute<int, int>
     */
    protected function courseId(): Attribute
    {
        return Attribute::make(
            get: fn (): int => $this->attributes['curso_id'],
            set: fn (int $value): array => ['curso_id' => $value],
        );
    }

    /**
     * @return Attribute<int, int>
     */
    protected function academicTermId(): Attribute
    {
        return Attribute::make(
            get: fn (): int => $this->attributes['periodo_academico_id'],
            set: fn (int $value): array => ['periodo_academico_id' => $value],
        );
    }

    /**
     * @return Attribute<int, int>
     */
    protected function sectionNumber(): Attribute
    {
        return Attribute::make(
            get: fn (): int => $this->attributes['numero'],
            set: fn (int $value): array => ['numero' => $value],
        );
    }
}
