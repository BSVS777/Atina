<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * DO-01-F3: fila de auditoría de una modificación efectiva.
 *
 * @property int $id
 * @property int|null $user_id
 * @property string $auditable_type
 * @property int $auditable_id
 * @property string $accion
 * @property array<string, array{anterior: mixed, nuevo: mixed}>|null $cambios
 * @property string|null $ip_address
 */
class Auditoria extends Model
{
    protected $table = 'auditorias';

    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'auditable_type',
        'auditable_id',
        'accion',
        'cambios',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'cambios' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
