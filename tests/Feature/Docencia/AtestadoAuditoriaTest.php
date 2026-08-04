<?php

use App\Models\Atestado;
use App\Models\Auditoria;
use App\Models\Docente;
use App\Models\Especialidad;
use App\Models\Role;
use App\Models\User;
use Atina\Docencia\Domain\Docente\GradoAcademico;
use Livewire\Livewire;

test('crear un atestado genera una fila de auditoría con usuario, fecha, campo, valor anterior y valor nuevo (DO-01-F3)', function () {
    $usuario = User::factory()->create();
    $usuario->roles()->attach(Role::where('name', 'Administrador')->firstOrFail());
    $this->actingAs($usuario);

    $docente = Docente::factory()->create();
    $especialidad = Especialidad::factory()->create();

    Livewire::test('pages::docencia.docente-perfil', ['docente' => $docente])
        ->set('especialidadId', $especialidad->id)
        ->set('grado', GradoAcademico::Maestria->value)
        ->set('institucion', 'UCR')
        ->set('anioObtencion', 2019)
        ->call('guardar')
        ->assertHasNoErrors();

    $atestado = Atestado::where('docente_id', $docente->id)->firstOrFail();

    $this->assertDatabaseHas('auditorias', [
        'user_id' => $usuario->id,
        'auditable_type' => Atestado::class,
        'auditable_id' => $atestado->id,
        'accion' => 'Creación',
    ]);

    $fila = Auditoria::where('auditable_id', $atestado->id)->firstOrFail();

    // toEqual (no toBe): MySQL almacena el JSON en un formato binario que no
    // garantiza el orden original de las claves del objeto (las reordena por
    // longitud), así que la comparación no puede depender del orden.
    expect($fila->created_at)->not->toBeNull()
        ->and($fila->cambios['institucion'])->toEqual(['anterior' => null, 'nuevo' => 'UCR']);
});

test('editar un atestado solo audita los campos que cambiaron, no todo el registro (RN-05)', function () {
    $usuario = User::factory()->create();
    $usuario->roles()->attach(Role::where('name', 'Administrador')->firstOrFail());
    $this->actingAs($usuario);

    $docente = Docente::factory()->create();
    $especialidad = Especialidad::factory()->create();
    $atestado = Atestado::factory()->create([
        'docente_id' => $docente->id,
        'especialidad_id' => $especialidad->id,
        'grado' => GradoAcademico::Licenciatura,
        'institucion' => 'UTN',
        'anio_obtencion' => 2015,
    ]);

    Livewire::test('pages::docencia.docente-perfil', ['docente' => $docente])
        ->call('abrirEditar', $atestado->id)
        ->set('institucion', 'UNA')
        ->call('guardar')
        ->assertHasNoErrors();

    $filaEdicion = Auditoria::where('auditable_id', $atestado->id)
        ->where('accion', 'Modificación')
        ->firstOrFail();

    expect($filaEdicion->cambios)->toEqual([
        'institucion' => ['anterior' => 'UTN', 'nuevo' => 'UNA'],
    ]);
});

test('un intento rechazado por falta de permiso no genera auditoría (A4)', function () {
    $usuario = User::factory()->create();
    $docente = Docente::factory()->create();
    $especialidad = Especialidad::factory()->create();

    $this->actingAs($usuario);

    Livewire::test('pages::docencia.docente-perfil', ['docente' => $docente])
        ->set('especialidadId', $especialidad->id)
        ->set('grado', GradoAcademico::Licenciatura->value)
        ->set('institucion', 'UTN')
        ->set('anioObtencion', 2020)
        ->call('guardar')
        ->assertForbidden();

    expect(Auditoria::count())->toBe(0);
});
