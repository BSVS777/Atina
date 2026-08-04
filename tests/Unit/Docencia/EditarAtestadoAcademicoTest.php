<?php

use Atina\Docencia\Application\Docente\Exceptions\AtestadoDuplicadoException;
use Atina\Docencia\Application\Docente\Exceptions\AtestadoNoEncontradoException;
use Atina\Docencia\Application\Docente\Exceptions\AutorizacionDenegadaException;
use Atina\Docencia\Application\Docente\UseCases\EditarAtestadoAcademico;
use Atina\Docencia\Application\Docente\UseCases\RegistrarAtestadoAcademico;
use Atina\Docencia\Domain\Docente\AnioObtencion;
use Atina\Docencia\Domain\Docente\Especialidad;
use Atina\Docencia\Domain\Docente\GradoAcademico;
use Atina\Docencia\Domain\Docente\PoliticaAutorizacionAtestado;
use Tests\Unit\Docencia\Fakes\AtestadoRepositoryEnMemoria;
use Tests\Unit\Docencia\Fakes\AuditLogRepositoryEnMemoria;

test('edita un atestado existente y solo audita los campos que cambiaron (DO-01-F1, DO-01-F3, RN-05)', function () {
    $atestados = new AtestadoRepositoryEnMemoria;
    $auditoria = new AuditLogRepositoryEnMemoria;
    $permisos = [PoliticaAutorizacionAtestado::PERMISO_GESTIONAR];

    $registrar = new RegistrarAtestadoAcademico($atestados, $auditoria);
    $creado = $registrar->ejecutar(1, $permisos, 1, new Especialidad(3, 'Ingeniería'), GradoAcademico::Licenciatura, 'UTN', new AnioObtencion(2018));

    $editar = new EditarAtestadoAcademico($atestados, $auditoria);
    $editado = $editar->ejecutar(
        actorUserId: 2,
        permisosActor: $permisos,
        atestadoId: $creado->id(),
        especialidad: new Especialidad(3, 'Ingeniería'),
        grado: GradoAcademico::Licenciatura,
        institucion: 'UCR',
        anioObtencion: new AnioObtencion(2018),
    );

    expect($editado->institucion())->toBe('UCR');

    $entradas = $auditoria->entradas();
    expect($entradas)->toHaveCount(2); // creación + edición

    $entradaEdicion = $entradas[1];
    expect($entradaEdicion->cambios())->toBe([
        'institucion' => ['anterior' => 'UTN', 'nuevo' => 'UCR'],
    ]);
});

test('editar sin cambios reales no genera una nueva entrada de auditoría (RN-05)', function () {
    $atestados = new AtestadoRepositoryEnMemoria;
    $auditoria = new AuditLogRepositoryEnMemoria;
    $permisos = [PoliticaAutorizacionAtestado::PERMISO_GESTIONAR];

    $registrar = new RegistrarAtestadoAcademico($atestados, $auditoria);
    $creado = $registrar->ejecutar(1, $permisos, 1, new Especialidad(3, 'Ingeniería'), GradoAcademico::Licenciatura, 'UTN', new AnioObtencion(2018));

    $editar = new EditarAtestadoAcademico($atestados, $auditoria);
    $editar->ejecutar(2, $permisos, $creado->id(), new Especialidad(3, 'Ingeniería'), GradoAcademico::Licenciatura, 'UTN', new AnioObtencion(2018));

    expect($auditoria->entradas())->toHaveCount(1); // solo la creación
});

test('rechaza edición de un actor sin el permiso atestados.gestionar (DO-01-F2)', function () {
    $atestados = new AtestadoRepositoryEnMemoria;
    $auditoria = new AuditLogRepositoryEnMemoria;
    $permisos = [PoliticaAutorizacionAtestado::PERMISO_GESTIONAR];

    $registrar = new RegistrarAtestadoAcademico($atestados, $auditoria);
    $creado = $registrar->ejecutar(1, $permisos, 1, new Especialidad(3, 'Ingeniería'), GradoAcademico::Licenciatura, 'UTN', new AnioObtencion(2018));

    $editar = new EditarAtestadoAcademico($atestados, $auditoria);
    $ejecutar = fn () => $editar->ejecutar(2, [], $creado->id(), new Especialidad(3, 'Ingeniería'), GradoAcademico::Licenciatura, 'UCR', new AnioObtencion(2018));

    expect($ejecutar)->toThrow(AutorizacionDenegadaException::class);
});

test('lanza excepción si el atestado a editar no existe', function () {
    $atestados = new AtestadoRepositoryEnMemoria;
    $auditoria = new AuditLogRepositoryEnMemoria;
    $editar = new EditarAtestadoAcademico($atestados, $auditoria);

    $ejecutar = fn () => $editar->ejecutar(
        1,
        [PoliticaAutorizacionAtestado::PERMISO_GESTIONAR],
        999,
        new Especialidad(1, 'Ingeniería'),
        GradoAcademico::Licenciatura,
        'UTN',
        new AnioObtencion(2020),
    );

    expect($ejecutar)->toThrow(AtestadoNoEncontradoException::class);
});

test('rechaza una edición que produciría un duplicado con otro atestado del mismo docente', function () {
    $atestados = new AtestadoRepositoryEnMemoria;
    $auditoria = new AuditLogRepositoryEnMemoria;
    $permisos = [PoliticaAutorizacionAtestado::PERMISO_GESTIONAR];

    $registrar = new RegistrarAtestadoAcademico($atestados, $auditoria);
    $registrar->ejecutar(1, $permisos, 1, new Especialidad(3, 'Ingeniería'), GradoAcademico::Licenciatura, 'UTN', new AnioObtencion(2018));
    $segundo = $registrar->ejecutar(1, $permisos, 1, new Especialidad(3, 'Ingeniería'), GradoAcademico::Maestria, 'UTN', new AnioObtencion(2020));

    $editar = new EditarAtestadoAcademico($atestados, $auditoria);
    $ejecutar = fn () => $editar->ejecutar(1, $permisos, $segundo->id(), new Especialidad(3, 'Ingeniería'), GradoAcademico::Licenciatura, 'UTN', new AnioObtencion(2020));

    expect($ejecutar)->toThrow(AtestadoDuplicadoException::class);
});
