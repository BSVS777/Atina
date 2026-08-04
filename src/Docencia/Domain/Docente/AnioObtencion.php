<?php

namespace Atina\Docencia\Domain\Docente;

use InvalidArgumentException;

/**
 * RN-03: el año de obtención no puede ser futuro ni implausible.
 */
final class AnioObtencion
{
    private const ANIO_MINIMO_PLAUSIBLE = 1950;

    private int $valor;

    public function __construct(int $valor)
    {
        $anioActual = (int) date('Y');

        if ($valor < self::ANIO_MINIMO_PLAUSIBLE || $valor > $anioActual) {
            throw new InvalidArgumentException(
                "El año de obtención ({$valor}) debe estar entre ".self::ANIO_MINIMO_PLAUSIBLE." y {$anioActual}."
            );
        }

        $this->valor = $valor;
    }

    public function valor(): int
    {
        return $this->valor;
    }
}
