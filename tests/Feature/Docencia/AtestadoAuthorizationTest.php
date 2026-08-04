<?php

use App\Models\Atestado;
use App\Models\Docente;
use App\Models\Especialidad;
use App\Models\Role;
use App\Models\User;
use Atina\Docencia\Domain\Docente\GradoAcademico;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;

test('un usuario sin permiso atestados.gestionar no puede abrir el formulario de alta (DO-01-F2)', function () {
    $usuario = User::factory()->create();
    $docente = Docente::factory()->create();

    $this->actingAs($usuario);

    // Livewire ejecuta las acciones a través de su propio endpoint interno de
    // actualización: Gate::authorize() lanza AuthorizationException, pero esa
    // excepción la maneja el handler HTTP de Laravel (Livewire la deja pasar
    // a propósito, ver RequestBroker::temporarilyDisableExceptionHandlingAndMiddleware)
    // y termina como una respuesta 403, no como una excepción propagada al test.
    Livewire::test('pages::docencia.docente-perfil', ['docente' => $docente])
        ->call('abrirCrear')
        ->assertForbidden();
});

test('un usuario sin permiso atestados.gestionar no puede guardar aunque manipule el estado del componente', function () {
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

    expect(Atestado::count())->toBe(0);
});

test('el gate gestionar-atestados refleja el permiso atestados.gestionar del RBAC compartido', function () {
    $sinPermiso = User::factory()->create();
    $conPermiso = User::factory()->create();
    $conPermiso->roles()->attach(Role::where('name', 'Coordinadora de Docencia')->firstOrFail());

    expect(Gate::forUser($sinPermiso)->denies('gestionar-atestados'))->toBeTrue()
        ->and(Gate::forUser($conPermiso)->allows('gestionar-atestados'))->toBeTrue();
});

test('un usuario con rol Administrador o Coordinadora de Docencia puede crear un atestado', function (string $rol) {
    $usuario = User::factory()->create();
    $usuario->roles()->attach(Role::where('name', $rol)->firstOrFail());

    $docente = Docente::factory()->create();
    $especialidad = Especialidad::factory()->create();

    $this->actingAs($usuario);

    Livewire::test('pages::docencia.docente-perfil', ['docente' => $docente])
        ->set('especialidadId', $especialidad->id)
        ->set('grado', GradoAcademico::Licenciatura->value)
        ->set('institucion', 'UTN')
        ->set('anioObtencion', 2020)
        ->call('guardar')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('atestados', [
        'docente_id' => $docente->id,
        'especialidad_id' => $especialidad->id,
        'institucion' => 'UTN',
    ]);
})->with(['Administrador', 'Coordinadora de Docencia']);
