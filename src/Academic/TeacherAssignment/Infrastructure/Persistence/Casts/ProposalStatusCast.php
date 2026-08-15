<?php

declare(strict_types=1);

namespace Src\Academic\TeacherAssignment\Infrastructure\Persistence\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Src\Academic\TeacherAssignment\Domain\ProposalStatus;

/**
 * Translates between the English ProposalStatus domain enum and the
 * literal Spanish values of asignaciones_docentes.estado — temporary
 * compatibility boundary, see Docs/DIARIO_DECISIONES_IA.md.
 *
 * @implements CastsAttributes<ProposalStatus, ProposalStatus|string>
 */
final class ProposalStatusCast implements CastsAttributes
{
    /** @var array<string, string> */
    public const TO_DATABASE = [
        'proposed' => 'Propuesta',
        'confirmed' => 'Confirmada',
        'rejected' => 'Rechazada',
    ];

    public static function toDatabaseValue(ProposalStatus $status): string
    {
        return self::TO_DATABASE[$status->value];
    }

    public function get(mixed $model, string $key, mixed $value, array $attributes): ?ProposalStatus
    {
        if ($value === null) {
            return null;
        }

        $english = array_search($value, self::TO_DATABASE, true);

        return $english === false ? null : ProposalStatus::from($english);
    }

    /**
     * @return array<string, string>
     */
    public function set(mixed $model, string $key, mixed $value, array $attributes): array
    {
        $status = $value instanceof ProposalStatus ? $value : ProposalStatus::from($value);

        return [$key => self::toDatabaseValue($status)];
    }
}
