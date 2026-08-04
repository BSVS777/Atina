<?php

namespace App\Models;

use Atina\Docencia\Domain\NotaTecnica\EstadoNotaTecnica;
use Database\Factories\NotaTecnicaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $asignacion_docente_id
 * @property int $archivo_id
 * @property int|null $user_id
 * @property Carbon $fecha_limite_ratificacion
 * @property EstadoNotaTecnica $estado
 * @property Carbon|null $ratificada_at
 */
class NotaTecnica extends Model
{
    /** @use HasFactory<NotaTecnicaFactory> */
    use HasFactory;

    protected $table = 'notas_tecnicas';

    protected $fillable = [
        'asignacion_docente_id',
        'archivo_id',
        'user_id',
        'fecha_limite_ratificacion',
        'estado',
        'ratificada_at',
    ];

    protected function casts(): array
    {
        return [
            'estado' => EstadoNotaTecnica::class,
            'fecha_limite_ratificacion' => 'date',
            'ratificada_at' => 'datetime',
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
     * @return BelongsTo<Archivo, $this>
     */
    public function archivo(): BelongsTo
    {
        return $this->belongsTo(Archivo::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
