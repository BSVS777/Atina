<?php

namespace App\Models;

use Database\Factories\AsignacionDocenteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Tabla propiedad del módulo "Oferta Académica" (otro grupo). Se define aquí,
 * mínimo, porque verificaciones_atinencia y notas_tecnicas cuelgan de ella.
 *
 * @property int $id
 * @property int $grupo_id
 * @property int $docente_id
 * @property string $jornada
 * @property string $condicion_nombramiento
 * @property string $estado
 */
class AsignacionDocente extends Model
{
    /** @use HasFactory<AsignacionDocenteFactory> */
    use HasFactory;

    protected $table = 'asignaciones_docentes';

    protected $fillable = ['grupo_id', 'docente_id', 'jornada', 'condicion_nombramiento', 'estado'];

    /**
     * @return BelongsTo<Docente, $this>
     */
    public function docente(): BelongsTo
    {
        return $this->belongsTo(Docente::class);
    }

    /**
     * @return HasMany<VerificacionAtinencia, $this>
     */
    public function verificaciones(): HasMany
    {
        return $this->hasMany(VerificacionAtinencia::class, 'asignacion_docente_id');
    }

    /**
     * @return HasOne<NotaTecnica, $this>
     */
    public function notaTecnica(): HasOne
    {
        return $this->hasOne(NotaTecnica::class, 'asignacion_docente_id');
    }
}
