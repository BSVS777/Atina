<?php

use Atina\Docencia\Domain\Docente\AnioObtencion;

test('acepta un año plausible dentro de rango', function () {
    expect((new AnioObtencion(2015))->valor())->toBe(2015);
});

test('rechaza un año futuro (RN-03)', function () {
    $anioFuturo = (int) date('Y') + 1;

    expect(fn () => new AnioObtencion($anioFuturo))->toThrow(InvalidArgumentException::class);
});

test('rechaza un año implausiblemente antiguo (RN-03)', function () {
    expect(fn () => new AnioObtencion(1900))->toThrow(InvalidArgumentException::class);
});
