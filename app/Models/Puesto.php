<?php

namespace App\Models;

use Database\Factories\PuestoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $nombre
 */
class Puesto extends Model
{
    /** @use HasFactory<PuestoFactory> */
    use HasFactory;

    protected $table = 'puestos';

    protected $fillable = ['nombre'];

    /**
     * @return HasMany<Docente, $this>
     */
    public function docentes(): HasMany
    {
        return $this->hasMany(Docente::class);
    }
}
