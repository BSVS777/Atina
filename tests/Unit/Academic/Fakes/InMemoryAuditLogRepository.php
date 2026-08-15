<?php

namespace Tests\Unit\Academic\Fakes;

use Src\Shared\Audit\Domain\Contracts\AuditLogRepositoryInterface;
use Src\Shared\Audit\Domain\Entities\AuditLogEntry;

final class InMemoryAuditLogRepository implements AuditLogRepositoryInterface
{
    /** @var list<AuditLogEntry> */
    private array $entries = [];

    public function record(AuditLogEntry $entry): void
    {
        $this->entries[] = $entry;
    }

    /** @return list<AuditLogEntry> */
    public function entries(): array
    {
        return $this->entries;
    }
}
