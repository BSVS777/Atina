<?php

namespace App\Models;

use Database\Factories\PositionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Maps to the professor-provided `puestos` table (institutional schema,
 * not owned by this project). `name` is a virtual attribute over the real
 * `nombre` column so every other layer of the app stays English.
 *
 * @property int $id
 * @property string $name
 * @property-read Collection<int, Teacher> $teachers
 */
#[Fillable(['name'])]
class Position extends Model
{
    /** @use HasFactory<PositionFactory> */
    use HasFactory;

    protected $table = 'puestos';

    /**
     * @return HasMany<Teacher, $this>
     */
    public function teachers(): HasMany
    {
        return $this->hasMany(Teacher::class, 'puesto_id');
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
}
