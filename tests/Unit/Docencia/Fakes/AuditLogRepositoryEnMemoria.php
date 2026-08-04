<?php

namespace Tests\Unit\Docencia\Fakes;

use Atina\Docencia\Application\Docente\Ports\AuditLogRepository;
use Atina\Docencia\Domain\Auditoria\AuditLogEntry;

final class AuditLogRepositoryEnMemoria implements AuditLogRepository
{
    /** @var list<AuditLogEntry> */
    private array $entradas = [];

    public function registrar(AuditLogEntry $entrada): void
    {
        $this->entradas[] = $entrada;
    }

    /** @return list<AuditLogEntry> */
    public function entradas(): array
    {
        return $this->entradas;
    }
}
