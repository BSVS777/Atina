<?php

namespace Database\Factories;

use App\Models\Archivo;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Archivo>
 */
class ArchivoFactory extends Factory
{
    protected $model = Archivo::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'archivable_type' => 'App\\Models\\TechnicalNote',
            'archivable_id' => 0,
            'tipo_documento' => 'Criterio Técnico',
            'nombre_original' => 'criterio-tecnico.pdf',
            'disco' => 'local',
            'ruta' => 'technical-notes/'.Str::random(20).'.pdf',
            'mime_type' => 'application/pdf',
            'tamano_bytes' => fake()->numberBetween(10_000, 500_000),
            'hash_sha256' => hash('sha256', Str::random(40)),
        ];
    }
}
