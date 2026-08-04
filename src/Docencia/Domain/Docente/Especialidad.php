<?php

namespace Atina\Docencia\Domain\Docente;

use InvalidArgumentException;

/**
 * Vocabulario controlado compartido con el catálogo de atinencia (DO-02).
 * Referencia a una fila de la tabla `especialidades` — el dominio no valida
 * su existencia, eso es responsabilidad del puerto/repositorio.
 */
final class Especialidad
{
    public function __construct(
        private readonly int $id,
        private readonly string $nombre,
    ) {
        if (trim($nombre) === '') {
            throw new InvalidArgumentException('El nombre de la especialidad no puede estar vacío.');
        }
    }

    public function id(): int
    {
        return $this->id;
    }

    public function nombre(): string
    {
        return $this->nombre;
    }
}
