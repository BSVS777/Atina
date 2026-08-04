<?php

namespace Atina\Docencia\Application\Docente\Exceptions;

use RuntimeException;

/**
 * DO-01-F2: el actor no tiene el permiso `atestados.gestionar`.
 */
final class AutorizacionDenegadaException extends RuntimeException
{
    public static function paraGestionarAtestados(): self
    {
        return new self('El usuario no tiene permiso para gestionar atestados académicos.');
    }
}
