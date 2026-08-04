<?php

namespace Atina\Docencia\Domain\Auditoria;

use InvalidArgumentException;

/**
 * DO-01-F3: registro de auditoría de una modificación efectiva (usuario,
 * fecha —asignada por el adaptador de persistencia—, campo, valor anterior,
 * valor nuevo). `cambios` es un mapa campo => [anterior, nuevo].
 */
final class AuditLogEntry
{
    public const ACCION_CREACION = 'Creación';

    public const ACCION_MODIFICACION = 'Modificación';

    /**
     * @param  array<string, array{anterior: mixed, nuevo: mixed}>  $cambios
     */
    public function __construct(
        private readonly ?int $usuarioId,
        private readonly string $auditableType,
        private readonly int $auditableId,
        private readonly string $accion,
        private readonly array $cambios,
    ) {
        if ($cambios === []) {
            throw new InvalidArgumentException('Una entrada de auditoría debe registrar al menos un cambio.');
        }
    }

    public function usuarioId(): ?int
    {
        return $this->usuarioId;
    }

    public function auditableType(): string
    {
        return $this->auditableType;
    }

    public function auditableId(): int
    {
        return $this->auditableId;
    }

    public function accion(): string
    {
        return $this->accion;
    }

    /**
     * @return array<string, array{anterior: mixed, nuevo: mixed}>
     */
    public function cambios(): array
    {
        return $this->cambios;
    }
}
