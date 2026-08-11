<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Maps to the professor-provided `auditorias` table (institutional
 * schema, not owned by this project). Only this model and
 * EloquentAuditLogRepository know the Spanish column names — see
 * Docs/DIARIO_DECISIONES_IA.md.
 *
 * @property int $id
 * @property int|null $user_id
 * @property string $auditable_type
 * @property int $auditable_id
 * @property string $accion
 * @property array<string, array{before: mixed, after: mixed}>|null $cambios
 * @property string|null $ip_address
 */
#[Fillable(['user_id', 'auditable_type', 'auditable_id', 'accion', 'cambios', 'ip_address'])]
class AuditLog extends Model
{
    protected $table = 'auditorias';

    const UPDATED_AT = null;

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
