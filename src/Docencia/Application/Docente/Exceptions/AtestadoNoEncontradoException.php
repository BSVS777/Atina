<?php

namespace Atina\Docencia\Application\Docente\Exceptions;

use RuntimeException;

final class AtestadoNoEncontradoException extends RuntimeException
{
    public static function conId(int $id): self
    {
        return new self("No existe un atestado con id {$id}.");
    }
}
