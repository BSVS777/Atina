<?php

namespace Database\Factories;

use App\Models\Archivo;
use App\Models\NotaTecnica;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Tabla de otro módulo (Gestión Documental) — factory mínima solo para poder
 * generar NotaTecnica (archivo_id es obligatorio, ver D13).
 *
 * @extends Factory<Archivo>
 */
class ArchivoFactory extends Factory
{
    protected $model = Archivo::class;

    public function definition(): array
    {
        return [
            'uuid' => fake()->uuid(),
            'archivable_type' => NotaTecnica::class,
            'archivable_id' => 0,
            'tipo_documento' => 'Criterio Técnico',
            'nombre_original' => 'criterio-tecnico.pdf',
            'disco' => 'local',
            'ruta' => 'notas-tecnicas/'.fake()->uuid().'.pdf',
            'mime_type' => 'application/pdf',
            'tamano_bytes' => fake()->numberBetween(10_000, 2_000_000),
            'hash_sha256' => hash('sha256', fake()->uuid()),
        ];
    }
}
