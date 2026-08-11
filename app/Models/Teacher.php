<?php

namespace App\Models;

use Database\Factories\TeacherFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Maps to the professor-provided `docentes` table (institutional schema,
 * not owned by this project). Every field is a virtual English attribute
 * over the real Spanish column so every other layer of the app stays
 * English — see AI_HARNESS.md's "external contract exception".
 *
 * @property int $id
 * @property int|null $user_id
 * @property int $position_id
 * @property string $national_id
 * @property string $first_name
 * @property string $last_name
 * @property string|null $second_last_name
 * @property string|null $estimated_workload
 * @property bool $active
 * @property-read Position $position
 * @property-read Collection<int, AcademicCredential> $academicCredentials
 */
#[Fillable(['user_id', 'position_id', 'national_id', 'first_name', 'last_name', 'second_last_name', 'estimated_workload', 'active'])]
class Teacher extends Model
{
    /** @use HasFactory<TeacherFactory> */
    use HasFactory;

    protected $table = 'docentes';

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Position, $this>
     */
    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class, 'puesto_id');
    }

    /**
     * @return HasMany<AcademicCredential, $this>
     */
    public function academicCredentials(): HasMany
    {
        return $this->hasMany(AcademicCredential::class, 'docente_id');
    }

    public function fullName(): string
    {
        return trim("{$this->first_name} {$this->last_name} {$this->second_last_name}");
    }

    /**
     * @return Attribute<int, int>
     */
    protected function positionId(): Attribute
    {
        return Attribute::make(
            get: fn (): int => $this->attributes['puesto_id'],
            set: fn (int $value): array => ['puesto_id' => $value],
        );
    }

    /**
     * @return Attribute<string, string>
     */
    protected function nationalId(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->attributes['cedula'],
            set: fn (string $value): array => ['cedula' => $value],
        );
    }

    /**
     * @return Attribute<string, string>
     */
    protected function firstName(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->attributes['nombre'],
            set: fn (string $value): array => ['nombre' => $value],
        );
    }

    /**
     * @return Attribute<string, string>
     */
    protected function lastName(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->attributes['primer_apellido'],
            set: fn (string $value): array => ['primer_apellido' => $value],
        );
    }

    /**
     * @return Attribute<string|null, string|null>
     */
    protected function secondLastName(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => $this->attributes['segundo_apellido'] ?? null,
            set: fn (?string $value): array => ['segundo_apellido' => $value],
        );
    }

    /**
     * @return Attribute<string|null, string|null>
     */
    protected function estimatedWorkload(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => $this->attributes['jornada_estimada'] ?? null,
            set: fn (?string $value): array => ['jornada_estimada' => $value],
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
