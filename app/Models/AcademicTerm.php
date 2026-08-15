<?php

namespace App\Models;

use Database\Factories\AcademicTermFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Maps to the professor-provided `periodos_academicos` table
 * (institutional schema, not owned by this project). `startDate` is the
 * "fecha de inicio del cuatrimestre destino" DO-02 resolves the
 * applicable affinity catalog version against.
 *
 * @property int $id
 * @property int $year
 * @property int $term_number
 * @property Carbon $start_date
 * @property Carbon $end_date
 * @property-read Collection<int, CourseGroup> $groups
 */
#[Fillable(['year', 'term_number', 'start_date', 'end_date'])]
class AcademicTerm extends Model
{
    /** @use HasFactory<AcademicTermFactory> */
    use HasFactory;

    protected $table = 'periodos_academicos';

    /**
     * @return HasMany<CourseGroup, $this>
     */
    public function groups(): HasMany
    {
        return $this->hasMany(CourseGroup::class, 'periodo_academico_id');
    }

    public function label(): string
    {
        return "{$this->year}-{$this->term_number}";
    }

    /**
     * @return Attribute<int, int>
     */
    protected function year(): Attribute
    {
        return Attribute::make(
            get: fn (): int => $this->attributes['anio'],
            set: fn (int $value): array => ['anio' => $value],
        );
    }

    /**
     * @return Attribute<int, int>
     */
    protected function termNumber(): Attribute
    {
        return Attribute::make(
            get: fn (): int => $this->attributes['cuatrimestre'],
            set: fn (int $value): array => ['cuatrimestre' => $value],
        );
    }

    /**
     * @return Attribute<Carbon, Carbon|string>
     */
    protected function startDate(): Attribute
    {
        return Attribute::make(
            get: fn (): Carbon => Carbon::parse($this->attributes['fecha_inicio']),
            set: fn (Carbon|string $value): array => ['fecha_inicio' => Carbon::parse($value)->toDateString()],
        );
    }

    /**
     * @return Attribute<Carbon, Carbon|string>
     */
    protected function endDate(): Attribute
    {
        return Attribute::make(
            get: fn (): Carbon => Carbon::parse($this->attributes['fecha_fin']),
            set: fn (Carbon|string $value): array => ['fecha_fin' => Carbon::parse($value)->toDateString()],
        );
    }
}
