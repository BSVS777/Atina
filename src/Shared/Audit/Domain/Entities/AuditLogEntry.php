<?php

declare(strict_types=1);

namespace Src\Shared\Audit\Domain\Entities;

use InvalidArgumentException;

/**
 * A record of one effective modification: who, when (assigned by the
 * persistence adapter), and a field => {before, after} map of what changed.
 */
final class AuditLogEntry
{
    public const ACTION_CREATED = 'created';

    public const ACTION_UPDATED = 'updated';

    public const ACTION_DELETED = 'deleted';

    /**
     * @param  array<string, array{before: mixed, after: mixed}>  $changes
     */
    public function __construct(
        private readonly ?int $actorUserId,
        private readonly string $auditableType,
        private readonly int $auditableId,
        private readonly string $action,
        private readonly array $changes,
    ) {
        if ($changes === []) {
            throw new InvalidArgumentException('An audit log entry must record at least one change.');
        }
    }

    public function actorUserId(): ?int
    {
        return $this->actorUserId;
    }

    public function auditableType(): string
    {
        return $this->auditableType;
    }

    public function auditableId(): int
    {
        return $this->auditableId;
    }

    public function action(): string
    {
        return $this->action;
    }

    /**
     * @return array<string, array{before: mixed, after: mixed}>
     */
    public function changes(): array
    {
        return $this->changes;
    }
}
