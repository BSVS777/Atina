<?php

declare(strict_types=1);

namespace Src\Academic\AcademicCredential\Infrastructure\Persistence\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Src\Academic\AcademicCredential\Domain\DegreeLevel;

/**
 * Translates between the English DegreeLevel domain enum and the literal
 * Spanish values of the professor-provided `atestados.grado` MySQL ENUM
 * column — a temporary compatibility boundary (see
 * Docs/DIARIO_DECISIONES_IA.md) until the professor ships an English
 * schema. Isolated here so no other layer needs to know the DB stores
 * 'Licenciatura' instead of 'licentiate'.
 *
 * @implements CastsAttributes<DegreeLevel, DegreeLevel|string>
 */
final class DegreeLevelCast implements CastsAttributes
{
    /**
     * @var array<string, string>
     */
    public const TO_DATABASE = [
        'diploma' => 'Diplomado',
        'bachelor' => 'Bachillerato',
        'licentiate' => 'Licenciatura',
        'master' => 'Maestría',
        'doctorate' => 'Doctorado',
    ];

    public static function toDatabaseValue(DegreeLevel $level): string
    {
        return self::TO_DATABASE[$level->value];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function get(mixed $model, string $key, mixed $value, array $attributes): ?DegreeLevel
    {
        if ($value === null) {
            return null;
        }

        $english = array_search($value, self::TO_DATABASE, true);

        if ($english === false) {
            throw new \UnexpectedValueException("Unmapped atestados.grado value: {$value}");
        }

        return DegreeLevel::from($english);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, string>
     */
    public function set(mixed $model, string $key, mixed $value, array $attributes): array
    {
        $level = $value instanceof DegreeLevel ? $value : DegreeLevel::from($value);

        return [$key => self::toDatabaseValue($level)];
    }
}
