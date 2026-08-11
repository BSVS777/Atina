<?php

namespace App\Models;

use Database\Factories\CourseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Maps to the professor-provided `cursos` table (institutional schema,
 * not owned by this project). Plain reference/context data — read-only
 * for this module. Every course is scoped to exactly one career; this
 * module does not model transversal/service courses (out of scope, see
 * Docs/DIARIO_DECISIONES_IA.md).
 *
 * @property int $id
 * @property int $career_id
 * @property string $code
 * @property string $name
 * @property bool $active
 * @property-read Career $career
 * @property-read Collection<int, CourseGroup> $groups
 */
#[Fillable(['career_id', 'code', 'name', 'active'])]
class Course extends Model
{
    /** @use HasFactory<CourseFactory> */
    use HasFactory;

    protected $table = 'cursos';

    /**
     * @return BelongsTo<Career, $this>
     */
    public function career(): BelongsTo
    {
        return $this->belongsTo(Career::class, 'carrera_id');
    }

    /**
     * @return HasMany<CourseGroup, $this>
     */
    public function groups(): HasMany
    {
        return $this->hasMany(CourseGroup::class, 'curso_id');
    }

    /**
     * @return Attribute<int, int>
     */
    protected function careerId(): Attribute
    {
        return Attribute::make(
            get: fn (): int => $this->attributes['carrera_id'],
            set: fn (int $value): array => ['carrera_id' => $value],
        );
    }

    /**
     * @return Attribute<string, string>
     */
    protected function code(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->attributes['codigo'],
            set: fn (string $value): array => ['codigo' => $value],
        );
    }

    /**
     * @return Attribute<string, string>
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->attributes['nombre'],
            set: fn (string $value): array => ['nombre' => $value],
        );
    }

    /**
     * @return Attribute<bool, bool>
     */
    protected function active(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => (bool) $this->attributes['activo'],
            set: fn (bool $value): array => ['activo' => $value],
        );
    }
}
