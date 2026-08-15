<?php

namespace App\Models;

use Database\Factories\CareerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Maps to the professor-provided `carreras` table (institutional schema,
 * not owned by this project). Plain reference/context data — read-only
 * for this module, see Docs/DIARIO_DECISIONES_IA.md.
 *
 * @property int $id
 * @property string $name
 * @property bool $active
 * @property-read Collection<int, Course> $courses
 */
#[Fillable(['name', 'active'])]
class Career extends Model
{
    /** @use HasFactory<CareerFactory> */
    use HasFactory;

    protected $table = 'carreras';

    /**
     * @return HasMany<Course, $this>
     */
    public function courses(): HasMany
    {
        return $this->hasMany(Course::class, 'carrera_id');
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
            get: fn (): bool => (bool) $this->attributes['activa'],
            set: fn (bool $value): array => ['activa' => $value],
        );
    }
}
