<?php

use App\Models\Atestado;
use App\Models\Docente;
use App\Models\Especialidad;
use App\Models\Role;
use App\Models\User;
use Atina\Docencia\Domain\Docente\GradoAcademico;
use Livewire\Livewire;

test('el perfil de un docente muestra sus atestados existentes en solo lectura (T3)', function () {
    $docente = Docente::factory()->create();
    $especialidad = Especialidad::factory()->create(['nombre' => 'Ingeniería en Alimentos']);
    Atestado::factory()->create([
        'docente_id' => $docente->id,
        'especialidad_id' => $especialidad->id,
        'institucion' => 'Universidad de Costa Rica',
    ]);

    $usuario = User::factory()->create();
    $this->actingAs($usuario);

    Livewire::test('pages::docencia.docente-perfil', ['docente' => $docente])
        ->assertSee($docente->nombreCompleto())
        ->assertSee('Ingeniería en Alimentos')
        ->assertSee('Universidad de Costa Rica')
        ->assertDontSee('Nuevo atestado');
});

test('crea y luego edita un atestado, viendo el cambio reflejado en el listado (DO-01-F1)', function () {
    $docente = Docente::factory()->create();
    $especialidad = Especialidad::factory()->create();
    $usuario = User::factory()->create();
    $usuario->roles()->attach(Role::where('name', 'Administrador')->firstOrFail());
    $this->actingAs($usuario);

    $componente = Livewire::test('pages::docencia.docente-perfil', ['docente' => $docente])
        ->set('especialidadId', $especialidad->id)
        ->set('grado', GradoAcademico::Doctorado->value)
        ->set('institucion', 'Universidad Nacional')
        ->set('anioObtencion', 2010)
        ->call('guardar')
        ->assertHasNoErrors()
        ->assertSee('Universidad Nacional');

    $atestado = Atestado::where('docente_id', $docente->id)->firstOrFail();

    $componente
        ->call('abrirEditar', $atestado->id)
        ->set('institucion', 'Universidad Estatal a Distancia')
        ->call('guardar')
        ->assertHasNoErrors()
        ->assertSee('Universidad Estatal a Distancia')
        ->assertDontSee('Universidad Nacional');

    expect($atestado->fresh()->institucion)->toBe('Universidad Estatal a Distancia');
});

test('rechaza un atestado duplicado desde el formulario y muestra el error sin persistirlo', function () {
    $docente = Docente::factory()->create();
    $especialidad = Especialidad::factory()->create();
    Atestado::factory()->create([
        'docente_id' => $docente->id,
        'especialidad_id' => $especialidad->id,
        'grado' => GradoAcademico::Licenciatura,
    ]);

    $usuario = User::factory()->create();
    $usuario->roles()->attach(Role::where('name', 'Administrador')->firstOrFail());
    $this->actingAs($usuario);

    Livewire::test('pages::docencia.docente-perfil', ['docente' => $docente])
        ->set('especialidadId', $especialidad->id)
        ->set('grado', GradoAcademico::Licenciatura->value)
        ->set('institucion', 'Otra institución')
        ->set('anioObtencion', 2015)
        ->call('guardar')
        ->assertHasErrors('form');

    expect(Atestado::where('docente_id', $docente->id)->count())->toBe(1);
});

test('valida los campos obligatorios y el rango de año antes de guardar', function () {
    $docente = Docente::factory()->create();
    $usuario = User::factory()->create();
    $usuario->roles()->attach(Role::where('name', 'Administrador')->firstOrFail());
    $this->actingAs($usuario);

    Livewire::test('pages::docencia.docente-perfil', ['docente' => $docente])
        ->set('especialidadId', null)
        ->set('grado', '')
        ->set('institucion', '')
        ->set('anioObtencion', (int) date('Y') + 5)
        ->call('guardar')
        ->assertHasErrors(['especialidadId', 'grado', 'institucion', 'anioObtencion']);
});
