<?php

namespace App\Models;

use Database\Factories\TeacherFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
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

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'estimated_workload' => 'decimal:2',
        ];
    }

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
        return $this->belongsTo(Position::class);
    }

    /**
     * @return HasMany<AcademicCredential, $this>
     */
    public function academicCredentials(): HasMany
    {
        return $this->hasMany(AcademicCredential::class);
    }

    public function fullName(): string
    {
        return trim("{$this->first_name} {$this->last_name} {$this->second_last_name}");
    }
}
