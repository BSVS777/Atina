<?php

namespace App\Models;

use Atina\Docencia\Domain\Verificacion\ResultadoVerificacion;
use Database\Factories\VerificacionAtinenciaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $asignacion_docente_id
 * @property int|null $catalogo_atinencia_id
 * @property int|null $user_id
 * @property ResultadoVerificacion $resultado
 * @property bool $es_provisional
 * @property string|null $justificacion
 */
class VerificacionAtinencia extends Model
{
    /** @use HasFactory<VerificacionAtinenciaFactory> */
    use HasFactory;

    protected $table = 'verificaciones_atinencia';

    /**
     * Snapshot inmutable (D11): solo se registra created_at, nunca se edita.
     */
    const UPDATED_AT = null;

    protected $fillable = [
        'asignacion_docente_id',
        'catalogo_atinencia_id',
        'user_id',
        'resultado',
        'es_provisional',
        'justificacion',
    ];

    protected function casts(): array
    {
        return [
            'resultado' => ResultadoVerificacion::class,
            'es_provisional' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<AsignacionDocente, $this>
     */
    public function asignacion(): BelongsTo
    {
        return $this->belongsTo(AsignacionDocente::class, 'asignacion_docente_id');
    }

    /**
     * @return BelongsTo<CatalogoAtinencia, $this>
     */
    public function catalogoAtinencia(): BelongsTo
    {
        return $this->belongsTo(CatalogoAtinencia::class, 'catalogo_atinencia_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
