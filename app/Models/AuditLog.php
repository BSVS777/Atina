<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string $auditable_type
 * @property int $auditable_id
 * @property string $action
 * @property array<string, array{before: mixed, after: mixed}>|null $changes
 * @property string|null $ip_address
 */
#[Fillable(['user_id', 'auditable_type', 'auditable_id', 'action', 'changes', 'ip_address'])]
class AuditLog extends Model
{
    const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'changes' => 'array',
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
