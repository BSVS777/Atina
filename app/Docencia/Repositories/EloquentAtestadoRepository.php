<?php

namespace App\Docencia\Repositories;

use App\Models\Atestado as AtestadoEloquent;
use Atina\Docencia\Application\Docente\Ports\AtestadoRepository;
use Atina\Docencia\Domain\Docente\AnioObtencion;
use Atina\Docencia\Domain\Docente\AtestadoAcademico;
use Atina\Docencia\Domain\Docente\Especialidad;
use Atina\Docencia\Domain\Docente\GradoAcademico;

class EloquentAtestadoRepository implements AtestadoRepository
{
    public function buscarPorId(int $id): ?AtestadoAcademico
    {
        $modelo = AtestadoEloquent::with('especialidad')->find($id);

        return $modelo ? $this->aDominio($modelo) : null;
    }

    public function existeParaDocenteEspecialidadGrado(
        int $docenteId,
        int $especialidadId,
        GradoAcademico $grado,
        ?int $exceptoAtestadoId = null,
    ): bool {
        return AtestadoEloquent::query()
            ->where('docente_id', $docenteId)
            ->where('especialidad_id', $especialidadId)
            ->where('grado', $grado)
            ->when($exceptoAtestadoId !== null, fn ($query) => $query->whereKeyNot($exceptoAtestadoId))
            ->exists();
    }

    public function guardar(AtestadoAcademico $atestado): AtestadoAcademico
    {
        $modelo = $atestado->id() !== null
            ? AtestadoEloquent::findOrFail($atestado->id())
            : new AtestadoEloquent;

        $modelo->fill([
            'docente_id' => $atestado->docenteId(),
            'especialidad_id' => $atestado->especialidad()->id(),
            'grado' => $atestado->grado(),
            'institucion' => $atestado->institucion(),
            'anio_obtencion' => $atestado->anioObtencion()->valor(),
        ]);
        $modelo->save();
        $modelo->load('especialidad');

        return $this->aDominio($modelo);
    }

    private function aDominio(AtestadoEloquent $modelo): AtestadoAcademico
    {
        return new AtestadoAcademico(
            $modelo->id,
            $modelo->docente_id,
            new Especialidad($modelo->especialidad->id, $modelo->especialidad->nombre),
            $modelo->grado,
            $modelo->institucion,
            new AnioObtencion($modelo->anio_obtencion),
        );
    }
}
