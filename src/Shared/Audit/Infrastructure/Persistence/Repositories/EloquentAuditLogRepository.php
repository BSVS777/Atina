<?php

declare(strict_types=1);

namespace Src\Shared\Audit\Infrastructure\Persistence\Repositories;

use App\Models\AcademicCredential;
use App\Models\AuditLog;
use Src\Academic\AcademicCredential\Application\UseCases\RegisterAcademicCredentialUseCase;
use Src\Shared\Audit\Domain\Contracts\AuditLogRepositoryInterface;
use Src\Shared\Audit\Domain\Entities\AuditLogEntry;

final class EloquentAuditLogRepository implements AuditLogRepositoryInterface
{
    /**
     * Translates a domain-level auditable-type identifier (e.g.
     * "academic_credential") into the full Eloquent class name expected by
     * auditorias.auditable_type.
     *
     * @var array<string, class-string>
     */
    private const AUDITABLE_TYPES = [
        RegisterAcademicCredentialUseCase::AUDITABLE_TYPE => AcademicCredential::class,
    ];

    /**
     * Translates the English AuditLogEntry action constant into the literal
     * Spanish value of the professor-provided `auditorias.accion` ENUM —
     * temporary compatibility boundary, see Docs/DIARIO_DECISIONES_IA.md.
     *
     * @var array<string, string>
     */
    private const ACTIONS = [
        AuditLogEntry::ACTION_CREATED => 'Creación',
        AuditLogEntry::ACTION_UPDATED => 'Modificación',
    ];

    public function record(AuditLogEntry $entry): void
    {
        AuditLog::query()->create([
            'user_id' => $entry->actorUserId(),
            'auditable_type' => self::AUDITABLE_TYPES[$entry->auditableType()] ?? $entry->auditableType(),
            'auditable_id' => $entry->auditableId(),
            'accion' => self::ACTIONS[$entry->action()] ?? $entry->action(),
            'cambios' => $entry->changes(),
            'ip_address' => request()->ip(),
        ]);
    }
}
