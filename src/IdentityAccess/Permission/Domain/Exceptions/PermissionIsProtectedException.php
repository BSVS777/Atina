<?php

declare(strict_types=1);

namespace Src\IdentityAccess\Permission\Domain\Exceptions;

use DomainException;

/**
 * Raised when an operation attempts to change the module/action identity
 * of an already-persisted Permission. Permission names are authorization
 * contracts referenced by Policies and RoleSeeder — renaming one would
 * silently break those references without renaming anything they check.
 */
final class PermissionIsProtectedException extends DomainException
{
    public static function forName(string $name): self
    {
        return new self("The permission [{$name}] is protected by the system and its module/action cannot be changed.");
    }

    public static function forDeletion(string $name): self
    {
        return new self("The permission [{$name}] is protected by the system and cannot be deleted.");
    }
}
