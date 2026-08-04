<?php

use App\Models\Atestado;
use App\Models\Docente;
use App\Models\NotaTecnica;
use App\Models\VerificacionAtinencia;
use Atina\Docencia\Domain\Docente\GradoAcademico;
use Atina\Docencia\Domain\NotaTecnica\EstadoNotaTecnica;
use Atina\Docencia\Domain\Verificacion\ResultadoVerificacion;

test('atestado factory crea la cadena completa docente/especialidad con el enum de grado', function () {
    $atestado = Atestado::factory()->create(['grado' => GradoAcademico::Diplomado]);

    expect($atestado->docente)->toBeInstanceOf(Docente::class)
        ->and($atestado->especialidad)->not->toBeNull()
        ->and($atestado->grado)->toBe(GradoAcademico::Diplomado);
});

test('un docente puede tener varios atestados con distinta especialidad o grado (D3)', function () {
    $docente = Docente::factory()->create();

    Atestado::factory()->count(3)->create(['docente_id' => $docente->id]);

    expect($docente->atestados()->count())->toBe(3);
});

test('verificacion atinencia factory resuelve la cadena hasta asignacion/grupo', function () {
    $verificacion = VerificacionAtinencia::factory()->noAtinente()->create();

    expect($verificacion->resultado)->toBe(ResultadoVerificacion::NoAtinente)
        ->and($verificacion->asignacion)->not->toBeNull()
        ->and($verificacion->updated_at)->toBeNull();
});

test('nota tecnica factory exige archivo obligatorio y estado inicial correcto', function () {
    $nota = NotaTecnica::factory()->create();

    expect($nota->archivo)->not->toBeNull()
        ->and($nota->estado)->toBe(EstadoNotaTecnica::RatificacionPendiente);
});
