<?php

namespace Atina\Docencia\Domain\Docente;

use InvalidArgumentException;

/**
 * DO-01-F1: atestado académico de un docente (grado, institución, año,
 * especialidad). Entity dentro del aggregate Docente.
 */
final class AtestadoAcademico
{
    public function __construct(
        private readonly ?int $id,
        private readonly int $docenteId,
        private readonly Especialidad $especialidad,
        private readonly GradoAcademico $grado,
        private readonly string $institucion,
        private readonly AnioObtencion $anioObtencion,
    ) {
        if (trim($institucion) === '') {
            throw new InvalidArgumentException('La institución es obligatoria.');
        }
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function docenteId(): int
    {
        return $this->docenteId;
    }

    public function especialidad(): Especialidad
    {
        return $this->especialidad;
    }

    public function grado(): GradoAcademico
    {
        return $this->grado;
    }

    public function institucion(): string
    {
        return $this->institucion;
    }

    public function anioObtencion(): AnioObtencion
    {
        return $this->anioObtencion;
    }

    public function conId(int $id): self
    {
        return new self($id, $this->docenteId, $this->especialidad, $this->grado, $this->institucion, $this->anioObtencion);
    }
}
