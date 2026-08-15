<?php

declare(strict_types=1);

namespace Src\Academic\TeacherAssignment\Domain;

/**
 * Mirrors verificaciones_atinencia.resultado (DO-02a). The four possible
 * outcomes the SRS describes: Atinente, No Atinente, Nota técnica, Sin
 * catálogo.
 */
enum VerificationResult: string
{
    case Matched = 'matched';
    case NotMatched = 'not_matched';
    case TechnicalNote = 'technical_note';
    case NoCatalog = 'no_catalog';
}
