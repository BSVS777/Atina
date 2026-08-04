<?php

namespace Atina\Docencia\Application\Docente\Ports;

use Atina\Docencia\Domain\Docente\AtestadoAcademico;
use Atina\Docencia\Domain\Docente\GradoAcademico;

interface AtestadoRepository
{
    public function buscarPorId(int $id): ?AtestadoAcademico;

    /**
     * RN-... (UNIQUE docente_id, especialidad_id, grado): un docente no
     * puede tener dos atestados con la misma combinación especialidad+grado.
     */
    public function existeParaDocenteEspecialidadGrado(
        int $docenteId,
        int $especialidadId,
        GradoAcademico $grado,
        ?int $exceptoAtestadoId = null,
    ): bool;

    public function guardar(AtestadoAcademico $atestado): AtestadoAcademico;
}
