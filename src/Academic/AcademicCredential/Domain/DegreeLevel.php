<?php

declare(strict_types=1);

namespace Src\Academic\AcademicCredential\Domain;

/**
 * Mirrors academic_credentials.degree_level. Five values: the Costa Rican
 * academic-degree ladder used by the shared institutional schema this was
 * ported from (Diplomado/Bachillerato/Licenciatura/Maestría/Doctorado).
 */
enum DegreeLevel: string
{
    case Diploma = 'diploma';
    case Bachelor = 'bachelor';
    case Licentiate = 'licentiate';
    case Master = 'master';
    case Doctorate = 'doctorate';
}
