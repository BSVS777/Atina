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
     * audit_logs.auditable_type.
     *
     * @var array<string, class-string>
     */
    private const AUDITABLE_TYPES = [
        RegisterAcademicCredentialUseCase::AUDITABLE_TYPE => AcademicCredential::class,
    ];

    public function record(AuditLogEntry $entry): void
    {
        AuditLog::query()->create([
            'user_id' => $entry->actorUserId(),
            'auditable_type' => self::AUDITABLE_TYPES[$entry->auditableType()] ?? $entry->auditableType(),
            'auditable_id' => $entry->auditableId(),
            'action' => $entry->action(),
            'changes' => $entry->changes(),
            'ip_address' => request()->ip(),
        ]);
    }
}
