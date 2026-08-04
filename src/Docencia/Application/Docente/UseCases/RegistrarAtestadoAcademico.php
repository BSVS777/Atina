<?php

namespace Atina\Docencia\Application\Docente\UseCases;

use Atina\Docencia\Application\Docente\Exceptions\AtestadoDuplicadoException;
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
 * DO-01-F1 (alta) + DO-01-F2 (autorización) + DO-01-F3 (auditoría).
 */
final class RegistrarAtestadoAcademico
{
    public const TIPO_AUDITORIA = 'atestado';

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
        int $docenteId,
        Especialidad $especialidad,
        GradoAcademico $grado,
        string $institucion,
        AnioObtencion $anioObtencion,
    ): AtestadoAcademico {
        if (! PoliticaAutorizacionAtestado::puedeGestionar($permisosActor)) {
            throw AutorizacionDenegadaException::paraGestionarAtestados();
        }

        if ($this->atestados->existeParaDocenteEspecialidadGrado($docenteId, $especialidad->id(), $grado)) {
            throw AtestadoDuplicadoException::paraDocenteEspecialidadGrado();
        }

        $nuevo = new AtestadoAcademico(null, $docenteId, $especialidad, $grado, $institucion, $anioObtencion);
        $guardado = $this->atestados->guardar($nuevo);
        $idGuardado = $guardado->id() ?? throw new \LogicException('El repositorio debe devolver el atestado guardado con id.');

        $this->auditoria->registrar(new AuditLogEntry(
            usuarioId: $actorUserId,
            auditableType: self::TIPO_AUDITORIA,
            auditableId: $idGuardado,
            accion: AuditLogEntry::ACCION_CREACION,
            cambios: [
                'especialidad_id' => ['anterior' => null, 'nuevo' => $especialidad->id()],
                'grado' => ['anterior' => null, 'nuevo' => $grado->value],
                'institucion' => ['anterior' => null, 'nuevo' => $institucion],
                'anio_obtencion' => ['anterior' => null, 'nuevo' => $anioObtencion->valor()],
            ],
        ));

        return $guardado;
    }
}
