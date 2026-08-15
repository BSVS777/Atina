<?php

namespace App\Models;

use Database\Factories\AcademicCredentialFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Src\Academic\AcademicCredential\Domain\DegreeLevel;
use Src\Academic\AcademicCredential\Infrastructure\Persistence\Casts\DegreeLevelCast;

/**
 * Maps to the professor-provided `atestados` table (institutional schema,
 * not owned by this project). This Eloquent model, and the repository
 * that consumes it, are the only places aware of the Spanish column
 * names — the Domain/Application layers only ever see the English
 * AcademicCredential entity. Temporary compatibility layer, see
 * Docs/DIARIO_DECISIONES_IA.md.
 *
 * @property int $id
 * @property int $docente_id
 * @property int $especialidad_id
 * @property DegreeLevel $grado
 * @property string $institucion
 * @property int $anio_obtencion
 * @property-read Teacher $teacher
 * @property-read Specialty $specialty
 */
#[Fillable(['docente_id', 'especialidad_id', 'grado', 'institucion', 'anio_obtencion'])]
class AcademicCredential extends Model
{
    /** @use HasFactory<AcademicCredentialFactory> */
    use HasFactory;

    protected $table = 'atestados';

    protected function casts(): array
    {
        return [
            'grado' => DegreeLevelCast::class,
            'anio_obtencion' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Teacher, $this>
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class, 'docente_id');
    }

    /**
     * @return BelongsTo<Specialty, $this>
     */
    public function specialty(): BelongsTo
    {
        return $this->belongsTo(Specialty::class, 'especialidad_id');
    }
}
