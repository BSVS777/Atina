<?php

namespace Atina\Docencia\Application\Docente\Ports;

use Atina\Docencia\Domain\Auditoria\AuditLogEntry;

interface AuditLogRepository
{
    public function registrar(AuditLogEntry $entrada): void;
}
