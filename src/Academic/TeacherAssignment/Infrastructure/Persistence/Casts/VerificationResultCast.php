<?php

declare(strict_types=1);

namespace Src\Academic\TeacherAssignment\Infrastructure\Persistence\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Src\Academic\TeacherAssignment\Domain\VerificationResult;

/**
 * Translates between the English VerificationResult domain enum and the
 * literal Spanish values of verificaciones_atinencia.resultado —
 * temporary compatibility boundary, see Docs/DIARIO_DECISIONES_IA.md.
 *
 * @implements CastsAttributes<VerificationResult, VerificationResult|string>
 */
final class VerificationResultCast implements CastsAttributes
{
    /** @var array<string, string> */
    public const TO_DATABASE = [
        'matched' => 'Atinente',
        'not_matched' => 'No Atinente',
        'technical_note' => 'Nota técnica',
        'no_catalog' => 'Sin catálogo',
    ];

    public static function toDatabaseValue(VerificationResult $result): string
    {
        return self::TO_DATABASE[$result->value];
    }

    public function get(mixed $model, string $key, mixed $value, array $attributes): ?VerificationResult
    {
        if ($value === null) {
            return null;
        }

        $english = array_search($value, self::TO_DATABASE, true);

        return $english === false ? null : VerificationResult::from($english);
    }

    /**
     * @return array<string, string>
     */
    public function set(mixed $model, string $key, mixed $value, array $attributes): array
    {
        $result = $value instanceof VerificationResult ? $value : VerificationResult::from($value);

        return [$key => self::toDatabaseValue($result)];
    }
}
