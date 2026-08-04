<?php

namespace App\Models;

use Database\Factories\CursoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Tabla propiedad del módulo "Oferta Académica" (otro grupo). Se define aquí,
 * mínimo, solo porque catalogos_atinencia.curso_id lo referencia.
 *
 * @property int $id
 * @property int|null $carrera_id
 * @property string $codigo
 * @property string $nombre
 * @property bool $es_servicio
 * @property bool $activo
 */
class Curso extends Model
{
    /** @use HasFactory<CursoFactory> */
    use HasFactory;

    protected $table = 'cursos';

    protected $fillable = ['carrera_id', 'codigo', 'nombre', 'es_servicio', 'activo'];

    protected function casts(): array
    {
        return [
            'es_servicio' => 'boolean',
            'activo' => 'boolean',
        ];
    }

    /**
     * @return HasMany<CatalogoAtinencia, $this>
     */
    public function catalogosAtinencia(): HasMany
    {
        return $this->hasMany(CatalogoAtinencia::class);
    }
}
