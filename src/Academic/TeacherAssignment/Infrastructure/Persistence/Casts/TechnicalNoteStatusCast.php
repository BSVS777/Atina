<?php

declare(strict_types=1);

namespace Src\Academic\TeacherAssignment\Infrastructure\Persistence\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Src\Academic\TeacherAssignment\Domain\TechnicalNoteStatus;

/**
 * Translates between the English TechnicalNoteStatus domain enum and the
 * literal Spanish values of notas_tecnicas.estado — temporary
 * compatibility boundary, see Docs/DIARIO_DECISIONES_IA.md.
 *
 * @implements CastsAttributes<TechnicalNoteStatus, TechnicalNoteStatus|string>
 */
final class TechnicalNoteStatusCast implements CastsAttributes
{
    /** @var array<string, string> */
    public const TO_DATABASE = [
        'pending_ratification' => 'Ratificación pendiente',
        'ratified' => 'Ratificada',
        'expired' => 'Vencida',
        'rejected' => 'Rechazada',
    ];

    public static function toDatabaseValue(TechnicalNoteStatus $status): string
    {
        return self::TO_DATABASE[$status->value];
    }

    public function get(mixed $model, string $key, mixed $value, array $attributes): ?TechnicalNoteStatus
    {
        if ($value === null) {
            return null;
        }

        $english = array_search($value, self::TO_DATABASE, true);

        return $english === false ? null : TechnicalNoteStatus::from($english);
    }

    /**
     * @return array<string, string>
     */
    public function set(mixed $model, string $key, mixed $value, array $attributes): array
    {
        $status = $value instanceof TechnicalNoteStatus ? $value : TechnicalNoteStatus::from($value);

        return [$key => self::toDatabaseValue($status)];
    }
}
