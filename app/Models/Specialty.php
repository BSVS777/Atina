<?php

namespace App\Models;

use Database\Factories\SpecialtyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Maps to the professor-provided `especialidades` table (institutional
 * schema, not owned by this project). `name` is a virtual attribute over
 * the real `nombre` column so every other layer of the app stays English.
 *
 * @property int $id
 * @property string $name
 * @property-read Collection<int, AcademicCredential> $academicCredentials
 */
#[Fillable(['name'])]
class Specialty extends Model
{
    /** @use HasFactory<SpecialtyFactory> */
    use HasFactory;

    protected $table = 'especialidades';

    /**
     * @return HasMany<AcademicCredential, $this>
     */
    public function academicCredentials(): HasMany
    {
        return $this->hasMany(AcademicCredential::class, 'especialidad_id');
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
