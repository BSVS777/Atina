<?php

namespace Atina\Docencia\Application\Docente\UseCases;

use Atina\Docencia\Application\Docente\Exceptions\AtestadoDuplicadoException;
use Atina\Docencia\Application\Docente\Exceptions\AtestadoNoEncontradoException;
use Atina\Docencia\Application\Docente\Exceptions\AutorizacionDenegadaException;
use Atina\Docencia\Application\Docente\Ports\AtestadoRepository;
use Atina\Docencia\Application\Docente\Ports\AuditLogRepository;
use Atina\Docencia\Domain\Auditoria\AuditLogEntry;
use Atina\Docencia\Domain\Docente\AnioObtencion;
use Atina\Docencia\Domain\Docente\AtestadoAcademico;
use Atina\Docencia\Domain\Docente\Especialidad;
use Atina\Docencia\Domain\Docente\GradoAcademico;
use Atina\Docencia\Domain\Docente\PoliticaAutorizacionAtestado;

/**
 * DO-01-F1 (edición) + DO-01-F2 (autorización) + DO-01-F3 (auditoría).
 * RN-05: solo la modificación efectiva (con al menos un campo cambiado)
 * genera auditoría.
 */
final class EditarAtestadoAcademico
{
    public function __construct(
        private readonly AtestadoRepository $atestados,
        private readonly AuditLogRepository $auditoria,
    ) {}

    /**
     * @param  list<string>  $permisosActor
     */
    public function ejecutar(
        ?int $actorUserId,
        array $permisosActor,
        int $atestadoId,
        Especialidad $especialidad,
        GradoAcademico $grado,
        string $institucion,
        AnioObtencion $anioObtencion,
    ): AtestadoAcademico {
        if (! PoliticaAutorizacionAtestado::puedeGestionar($permisosActor)) {
            throw AutorizacionDenegadaException::paraGestionarAtestados();
        }

        $existente = $this->atestados->buscarPorId($atestadoId)
            ?? throw AtestadoNoEncontradoException::conId($atestadoId);

        if ($this->atestados->existeParaDocenteEspecialidadGrado(
            $existente->docenteId(),
            $especialidad->id(),
            $grado,
            exceptoAtestadoId: $atestadoId,
        )) {
            throw AtestadoDuplicadoException::paraDocenteEspecialidadGrado();
        }

        $cambios = $this->calcularCambios($existente, $especialidad, $grado, $institucion, $anioObtencion);

        $actualizado = new AtestadoAcademico(
            $atestadoId,
            $existente->docenteId(),
            $especialidad,
            $grado,
            $institucion,
            $anioObtencion,
        );
        $guardado = $this->atestados->guardar($actualizado);

        if ($cambios !== []) {
            $this->auditoria->registrar(new AuditLogEntry(
                usuarioId: $actorUserId,
                auditableType: RegistrarAtestadoAcademico::TIPO_AUDITORIA,
                auditableId: $atestadoId,
                accion: AuditLogEntry::ACCION_MODIFICACION,
                cambios: $cambios,
            ));
        }

        return $guardado;
    }

    /**
     * @return array<string, array{anterior: mixed, nuevo: mixed}>
     */
    private function calcularCambios(
        AtestadoAcademico $existente,
        Especialidad $especialidad,
        GradoAcademico $grado,
        string $institucion,
        AnioObtencion $anioObtencion,
    ): array {
        $cambios = [];

        if ($existente->especialidad()->id() !== $especialidad->id()) {
            $cambios['especialidad_id'] = ['anterior' => $existente->especialidad()->id(), 'nuevo' => $especialidad->id()];
        }

        if ($existente->grado() !== $grado) {
            $cambios['grado'] = ['anterior' => $existente->grado()->value, 'nuevo' => $grado->value];
        }

        if ($existente->institucion() !== $institucion) {
            $cambios['institucion'] = ['anterior' => $existente->institucion(), 'nuevo' => $institucion];
        }

        if ($existente->anioObtencion()->valor() !== $anioObtencion->valor()) {
            $cambios['anio_obtencion'] = ['anterior' => $existente->anioObtencion()->valor(), 'nuevo' => $anioObtencion->valor()];
        }

        return $cambios;
    }
}
