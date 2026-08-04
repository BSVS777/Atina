<?php

namespace Atina\Docencia\Domain\Docente;

/**
 * Refleja atestados.grado (database/sql/schema_compartido.sql, sección 6).
 * 5 valores — el enunciado original solo mencionaba 4, falta "Diplomado".
 */
enum GradoAcademico: string
{
    case Diplomado = 'Diplomado';
    case Bachillerato = 'Bachillerato';
    case Licenciatura = 'Licenciatura';
    case Maestria = 'Maestría';
    case Doctorado = 'Doctorado';
}
