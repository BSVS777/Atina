<?php

namespace App\Models;

use Database\Factories\SpecialtyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property-read \Illuminate\Database\Eloquent\Collection<int, AcademicCredential> $academicCredentials
 */
#[Fillable(['name'])]
class Specialty extends Model
{
    /** @use HasFactory<SpecialtyFactory> */
    use HasFactory;

    /**
     * @return HasMany<AcademicCredential, $this>
     */
    public function academicCredentials(): HasMany
    {
        return $this->hasMany(AcademicCredential::class);
    }
}
