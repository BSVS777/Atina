<?php

namespace App\Models;

use Database\Factories\AffinityCatalogVersionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

/**
 * Maps to the professor-provided `catalogos_atinencia` table
 * (institutional schema, not owned by this project). Only this model,
 * its pivot, and EloquentAffinityCatalogVersionRepository know the
 * Spanish column names — the Domain/Application layers only ever see
 * the English AffinityCatalogVersion entity. See
 * Docs/DIARIO_DECISIONES_IA.md.
 *
 * @property int $id
 * @property int $curso_id
 * @property int $version
 * @property string $acuerdo
 * @property string $numero_gaceta
 * @property Carbon $vigencia_inicio
 * @property Carbon|null $vigencia_fin
 * @property-read Course $course
 * @property-read Collection<int, Specialty> $especialidadesAtinentes
 */
#[Fillable(['curso_id', 'version', 'acuerdo', 'numero_gaceta', 'vigencia_inicio', 'vigencia_fin'])]
class AffinityCatalogVersion extends Model
{
    /** @use HasFactory<AffinityCatalogVersionFactory> */
    use HasFactory;

    protected $table = 'catalogos_atinencia';

    protected function casts(): array
    {
        return [
            'vigencia_inicio' => 'date',
            'vigencia_fin' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'curso_id');
    }

    /**
     * @return BelongsToMany<Specialty, $this>
     */
    public function especialidadesAtinentes(): BelongsToMany
    {
        return $this->belongsToMany(Specialty::class, 'catalogo_atinencia_especialidad', 'catalogo_atinencia_id', 'especialidad_id');
    }
}
