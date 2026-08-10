<?php

namespace App\Models;

use Database\Factories\AcademicCredentialFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Src\Academic\AcademicCredential\Domain\DegreeLevel;

/**
 * @property int $id
 * @property int $teacher_id
 * @property int $specialty_id
 * @property DegreeLevel $degree_level
 * @property string $institution
 * @property int $year_obtained
 * @property-read Teacher $teacher
 * @property-read Specialty $specialty
 */
#[Fillable(['teacher_id', 'specialty_id', 'degree_level', 'institution', 'year_obtained'])]
class AcademicCredential extends Model
{
    /** @use HasFactory<AcademicCredentialFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'degree_level' => DegreeLevel::class,
            'year_obtained' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Teacher, $this>
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    /**
     * @return BelongsTo<Specialty, $this>
     */
    public function specialty(): BelongsTo
    {
        return $this->belongsTo(Specialty::class);
    }
}
