<?php

namespace App\Docencia\Repositories;

use App\Models\Atestado;
use App\Models\Auditoria;
use Atina\Docencia\Application\Docente\Ports\AuditLogRepository;
use Atina\Docencia\Application\Docente\UseCases\RegistrarAtestadoAcademico;
use Atina\Docencia\Domain\Auditoria\AuditLogEntry;

class EloquentAuditLogRepository implements AuditLogRepository
{
    /**
     * Traduce el identificador de dominio (p. ej. "atestado") a la clase
     * Eloquent completa que espera `auditorias.auditable_type`.
     *
     * @var array<string, class-string>
     */
    private const TIPOS = [
        RegistrarAtestadoAcademico::TIPO_AUDITORIA => Atestado::class,
    ];

    public function registrar(AuditLogEntry $entrada): void
    {
        Auditoria::create([
            'user_id' => $entrada->usuarioId(),
            'auditable_type' => self::TIPOS[$entrada->auditableType()] ?? $entrada->auditableType(),
            'auditable_id' => $entrada->auditableId(),
            'accion' => $entrada->accion(),
            'cambios' => $entrada->cambios(),
            'ip_address' => request()->ip(),
        ]);
    }
}
