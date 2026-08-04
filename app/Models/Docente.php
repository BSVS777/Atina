<?php

namespace App\Models;

use Database\Factories\DocenteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int|null $user_id
 * @property int $puesto_id
 * @property string $cedula
 * @property string $nombre
 * @property string $primer_apellido
 * @property string|null $segundo_apellido
 * @property string|null $jornada_estimada
 * @property bool $activo
 */
class Docente extends Model
{
    /** @use HasFactory<DocenteFactory> */
    use HasFactory;

    protected $table = 'docentes';

    protected $fillable = [
        'user_id',
        'puesto_id',
        'cedula',
        'nombre',
        'primer_apellido',
        'segundo_apellido',
        'jornada_estimada',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'jornada_estimada' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Puesto, $this>
     */
    public function puesto(): BelongsTo
    {
        return $this->belongsTo(Puesto::class);
    }

    /**
     * @return HasMany<Atestado, $this>
     */
    public function atestados(): HasMany
    {
        return $this->hasMany(Atestado::class);
    }

    /**
     * @return HasMany<AsignacionDocente, $this>
     */
    public function asignaciones(): HasMany
    {
        return $this->hasMany(AsignacionDocente::class);
    }

    public function nombreCompleto(): string
    {
        return trim("{$this->nombre} {$this->primer_apellido} {$this->segundo_apellido}");
    }
}
