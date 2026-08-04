<?php

use App\Models\Docente;
use App\Models\User;
use Livewire\Livewire;

test('la lista de docentes es visible para cualquier usuario autenticado (T1)', function () {
    $docente = Docente::factory()->create(['nombre' => 'Ana', 'primer_apellido' => 'Rodríguez']);

    $usuario = User::factory()->create();
    $this->actingAs($usuario);

    Livewire::test('pages::docencia.docentes')
        ->assertSee('Ana Rodríguez');
});

test('la búsqueda filtra por nombre, apellido o cédula', function () {
    Docente::factory()->create(['nombre' => 'Ana', 'primer_apellido' => 'Rodríguez']);
    Docente::factory()->create(['nombre' => 'Carlos', 'primer_apellido' => 'Vargas']);

    $usuario = User::factory()->create();
    $this->actingAs($usuario);

    Livewire::test('pages::docencia.docentes')
        ->set('busqueda', 'Vargas')
        ->assertSee('Carlos Vargas')
        ->assertDontSee('Ana Rodríguez');
});
