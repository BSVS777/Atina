<?php

namespace App\Models;

use Database\Factories\CatalogoAtinenciaFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $curso_id
 * @property int $version
 * @property string $acuerdo
 * @property string $numero_gaceta
 * @property Carbon $vigencia_inicio
 * @property Carbon|null $vigencia_fin
 */
class CatalogoAtinencia extends Model
{
    /** @use HasFactory<CatalogoAtinenciaFactory> */
    use HasFactory;

    protected $table = 'catalogos_atinencia';

    protected $fillable = [
        'curso_id',
        'version',
        'acuerdo',
        'numero_gaceta',
        'vigencia_inicio',
        'vigencia_fin',
    ];

    protected function casts(): array
    {
        return [
            'vigencia_inicio' => 'date',
            'vigencia_fin' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Curso, $this>
     */
    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class);
    }

    /**
     * @return BelongsToMany<Especialidad, $this>
     */
    public function especialidadesAtinentes(): BelongsToMany
    {
        return $this->belongsToMany(Especialidad::class, 'catalogo_atinencia_especialidad');
    }

    /**
     * @return HasMany<VerificacionAtinencia, $this>
     */
    public function verificaciones(): HasMany
    {
        return $this->hasMany(VerificacionAtinencia::class, 'catalogo_atinencia_id');
    }
}
