<?php

use Atina\Docencia\Domain\Docente\PoliticaAutorizacionAtestado;

test('permite gestionar cuando el actor tiene el permiso atestados.gestionar (RN-04)', function () {
    expect(PoliticaAutorizacionAtestado::puedeGestionar(['atestados.gestionar']))->toBeTrue();
});

test('rechaza gestionar cuando el actor no tiene el permiso', function () {
    expect(PoliticaAutorizacionAtestado::puedeGestionar(['oferta.consultar']))->toBeFalse();
    expect(PoliticaAutorizacionAtestado::puedeGestionar([]))->toBeFalse();
});
