<?php

namespace Atina\Docencia\Application\Docente\Exceptions;

use RuntimeException;

final class AtestadoDuplicadoException extends RuntimeException
{
    public static function paraDocenteEspecialidadGrado(): self
    {
        return new self('Ese docente ya tiene un atestado con esa especialidad y grado.');
    }
}
