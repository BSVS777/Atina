<?php

declare(strict_types=1);

namespace Src\Shared\Audit\Domain\Contracts;

use Src\Shared\Audit\Domain\Entities\AuditLogEntry;

interface AuditLogRepositoryInterface
{
    public function record(AuditLogEntry $entry): void;
}
