<?php

namespace Atina\Docencia\Domain\NotaTecnica;

/**
 * Refleja notas_tecnicas.estado (database/sql/schema_compartido.sql, sección 8).
 * `Vencida` es terminal — no hay valor de "reapertura" (D14).
 */
enum EstadoNotaTecnica: string
{
    case RatificacionPendiente = 'Ratificación pendiente';
    case Ratificada = 'Ratificada';
    case Vencida = 'Vencida';
    case Rechazada = 'Rechazada';
}
