<?php

namespace Tests;

use Database\Seeders\GestionAcademicaUtnSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Fortify\Features;

abstract class TestCase extends BaseTestCase
{
    /**
     * RefreshDatabase corre este seeder (no el DatabaseSeeder completo, que
     * crea un usuario 'test@example.com' fijo y choca con los tests de auth
     * existentes) antes de cada test — nuestras factories de módulos ajenos
     * (Curso, AsignacionDocente) dependen de que esos datos de referencia
     * (roles, carreras, periodos académicos, modalidades, metas...) ya existan.
     */
    protected $seed = true;

    protected $seeder = GestionAcademicaUtnSeeder::class;

    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }
}
