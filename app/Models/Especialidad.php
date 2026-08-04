<?php

namespace App\Models;

use Database\Factories\EspecialidadFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $nombre
 */
class Especialidad extends Model
{
    /** @use HasFactory<EspecialidadFactory> */
    use HasFactory;

    protected $table = 'especialidades';

    protected $fillable = ['nombre'];

    /**
     * @return HasMany<Atestado, $this>
     */
    public function atestados(): HasMany
    {
        return $this->hasMany(Atestado::class);
    }

    /**
     * @return BelongsToMany<CatalogoAtinencia, $this>
     */
    public function catalogosAtinencia(): BelongsToMany
    {
        return $this->belongsToMany(CatalogoAtinencia::class, 'catalogo_atinencia_especialidad');
    }
}
