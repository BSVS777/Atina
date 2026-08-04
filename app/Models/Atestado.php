<?php

namespace App\Models;

use Atina\Docencia\Domain\Docente\GradoAcademico;
use Database\Factories\AtestadoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $docente_id
 * @property int $especialidad_id
 * @property GradoAcademico $grado
 * @property string $institucion
 * @property int $anio_obtencion
 */
class Atestado extends Model
{
    /** @use HasFactory<AtestadoFactory> */
    use HasFactory;

    protected $table = 'atestados';

    protected $fillable = [
        'docente_id',
        'especialidad_id',
        'grado',
        'institucion',
        'anio_obtencion',
    ];

    protected function casts(): array
    {
        return [
            'grado' => GradoAcademico::class,
            'anio_obtencion' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Docente, $this>
     */
    public function docente(): BelongsTo
    {
        return $this->belongsTo(Docente::class);
    }

    /**
     * @return BelongsTo<Especialidad, $this>
     */
    public function especialidad(): BelongsTo
    {
        return $this->belongsTo(Especialidad::class);
    }
}
