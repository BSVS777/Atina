<?php

use Atina\Docencia\Application\Docente\Exceptions\AtestadoDuplicadoException;
use Atina\Docencia\Application\Docente\Exceptions\AutorizacionDenegadaException;
use Atina\Docencia\Application\Docente\UseCases\RegistrarAtestadoAcademico;
use Atina\Docencia\Domain\Auditoria\AuditLogEntry;
use Atina\Docencia\Domain\Docente\AnioObtencion;
use Atina\Docencia\Domain\Docente\Especialidad;
use Atina\Docencia\Domain\Docente\GradoAcademico;
use Atina\Docencia\Domain\Docente\PoliticaAutorizacionAtestado;
use Tests\Unit\Docencia\Fakes\AtestadoRepositoryEnMemoria;
use Tests\Unit\Docencia\Fakes\AuditLogRepositoryEnMemoria;

test('un actor autorizado registra un atestado y genera auditoría de creación (DO-01-F1, DO-01-F3)', function () {
    $atestados = new AtestadoRepositoryEnMemoria;
    $auditoria = new AuditLogRepositoryEnMemoria;
    $caso = new RegistrarAtestadoAcademico($atestados, $auditoria);

    $atestado = $caso->ejecutar(
        actorUserId: 7,
        permisosActor: [PoliticaAutorizacionAtestado::PERMISO_GESTIONAR],
        docenteId: 1,
        especialidad: new Especialidad(3, 'Ingeniería en Sistemas'),
        grado: GradoAcademico::Licenciatura,
        institucion: 'UTN',
        anioObtencion: new AnioObtencion(2020),
    );

    expect($atestado->id())->not->toBeNull();

    $entradas = $auditoria->entradas();
    expect($entradas)->toHaveCount(1);

    $entrada = $entradas[0];
    expect($entrada->accion())->toBe(AuditLogEntry::ACCION_CREACION)
        ->and($entrada->usuarioId())->toBe(7)
        ->and($entrada->auditableId())->toBe($atestado->id())
        ->and($entrada->cambios()['institucion'])->toBe(['anterior' => null, 'nuevo' => 'UTN'])
        ->and($entrada->cambios()['grado'])->toBe(['anterior' => null, 'nuevo' => 'Licenciatura']);
});

test('un actor sin el permiso atestados.gestionar no puede registrar (DO-01-F2, RN-04)', function () {
    $atestados = new AtestadoRepositoryEnMemoria;
    $auditoria = new AuditLogRepositoryEnMemoria;
    $caso = new RegistrarAtestadoAcademico($atestados, $auditoria);

    $ejecutar = fn () => $caso->ejecutar(
        actorUserId: 7,
        permisosActor: ['oferta.consultar'],
        docenteId: 1,
        especialidad: new Especialidad(3, 'Ingeniería en Sistemas'),
        grado: GradoAcademico::Licenciatura,
        institucion: 'UTN',
        anioObtencion: new AnioObtencion(2020),
    );

    expect($ejecutar)->toThrow(AutorizacionDenegadaException::class);
    expect($auditoria->entradas())->toBeEmpty();
});

test('rechaza un atestado duplicado: misma especialidad y grado del mismo docente', function () {
    $atestados = new AtestadoRepositoryEnMemoria;
    $auditoria = new AuditLogRepositoryEnMemoria;
    $caso = new RegistrarAtestadoAcademico($atestados, $auditoria);
    $permisos = [PoliticaAutorizacionAtestado::PERMISO_GESTIONAR];

    $caso->ejecutar(1, $permisos, 1, new Especialidad(3, 'Ingeniería en Sistemas'), GradoAcademico::Licenciatura, 'UTN', new AnioObtencion(2020));

    $ejecutar = fn () => $caso->ejecutar(1, $permisos, 1, new Especialidad(3, 'Ingeniería en Sistemas'), GradoAcademico::Licenciatura, 'UCR', new AnioObtencion(2021));

    expect($ejecutar)->toThrow(AtestadoDuplicadoException::class);
});

test('permite el mismo grado en distinta especialidad, o la misma especialidad con distinto grado (D3)', function () {
    $atestados = new AtestadoRepositoryEnMemoria;
    $auditoria = new AuditLogRepositoryEnMemoria;
    $caso = new RegistrarAtestadoAcademico($atestados, $auditoria);
    $permisos = [PoliticaAutorizacionAtestado::PERMISO_GESTIONAR];

    $caso->ejecutar(1, $permisos, 1, new Especialidad(3, 'Ingeniería en Sistemas'), GradoAcademico::Licenciatura, 'UTN', new AnioObtencion(2020));
    $caso->ejecutar(1, $permisos, 1, new Especialidad(3, 'Ingeniería en Sistemas'), GradoAcademico::Maestria, 'UCR', new AnioObtencion(2022));
    $caso->ejecutar(1, $permisos, 1, new Especialidad(4, 'Ingeniería en Computación'), GradoAcademico::Licenciatura, 'ITCR', new AnioObtencion(2021));

    expect($auditoria->entradas())->toHaveCount(3);
});
