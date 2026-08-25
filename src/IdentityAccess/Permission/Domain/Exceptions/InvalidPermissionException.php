<?php

declare(strict_types=1);

namespace Src\IdentityAccess\Permission\Domain\Exceptions;

use DomainException;

/**
 * Raised when a (module, action) pair is not part of PermissionCatalog —
 * the last line of defense against a forged request creating a
 * permission the system's policies/seeders never reference.
 */
final class InvalidPermissionException extends DomainException
{
    public static function forCombination(string $module, string $action): self
    {
        return new self("[{$module}.{$action}] is not an official permission combination.");
    }
}
