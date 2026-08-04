<?php

namespace Atina\Docencia\Domain\Verificacion;

/**
 * Refleja verificaciones_atinencia.resultado (database/sql/schema_compartido.sql,
 * sección 7). `SinCatalogo` implica catalogo_atinencia_id NULL (D11).
 */
enum ResultadoVerificacion: string
{
    case Atinente = 'Atinente';
    case NoAtinente = 'No Atinente';
    case NotaTecnica = 'Nota técnica';
    case SinCatalogo = 'Sin catálogo';
}
