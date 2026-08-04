<?php

namespace Tests\Unit\Docencia\Fakes;

use Atina\Docencia\Application\Docente\Ports\AtestadoRepository;
use Atina\Docencia\Domain\Docente\AtestadoAcademico;
use Atina\Docencia\Domain\Docente\GradoAcademico;

final class AtestadoRepositoryEnMemoria implements AtestadoRepository
{
    /** @var array<int, AtestadoAcademico> */
    private array $atestados = [];

    private int $siguienteId = 1;

    public function buscarPorId(int $id): ?AtestadoAcademico
    {
        return $this->atestados[$id] ?? null;
    }

    public function existeParaDocenteEspecialidadGrado(
        int $docenteId,
        int $especialidadId,
        GradoAcademico $grado,
        ?int $exceptoAtestadoId = null,
    ): bool {
        foreach ($this->atestados as $atestado) {
            if ($atestado->id() === $exceptoAtestadoId) {
                continue;
            }

            if ($atestado->docenteId() === $docenteId
                && $atestado->especialidad()->id() === $especialidadId
                && $atestado->grado() === $grado) {
                return true;
            }
        }

        return false;
    }

    public function guardar(AtestadoAcademico $atestado): AtestadoAcademico
    {
        $id = $atestado->id() ?? $this->siguienteId++;
        $guardado = $atestado->id() === null ? $atestado->conId($id) : $atestado;
        $this->atestados[$id] = $guardado;

        return $guardado;
    }
}
