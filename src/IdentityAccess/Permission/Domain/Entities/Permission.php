<?php

declare(strict_types=1);

namespace Src\IdentityAccess\Permission\Domain\Entities;

use Src\IdentityAccess\Permission\Domain\Exceptions\InvalidPermissionException;
use Src\IdentityAccess\Permission\Domain\Exceptions\PermissionIsProtectedException;
use Src\IdentityAccess\Permission\Domain\ValueObjects\PermissionCatalog;

/**
 * Permission — Aggregate Root of the IdentityAccess bounded context.
 * Pure PHP, zero framework coupling. Uniqueness of (module, action) is
 * enforced at Infrastructure (DB unique index) and validated at
 * Presentation — the Domain layer stays free of repository access.
 */
final class Permission
{
    private function __construct(
        private readonly ?int $id,
        private string $module,
        private string $action,
    ) {}

    /**
     * New permissions must belong to PermissionCatalog — the closed,
     * official vocabulary. This is the Domain-level backstop behind
     * PermissionForm's validation: it still holds even if a use case is
     * ever called from somewhere other than the Livewire form.
     */
    public static function create(string $module, string $action): self
    {
        if (! PermissionCatalog::isOfficial($module, $action)) {
            throw InvalidPermissionException::forCombination($module, $action);
        }

        return new self(id: null, module: $module, action: $action);
    }

    /**
     * Reconstituting from storage never validates against the catalog —
     * a pre-existing/legacy row that predates or falls outside the
     * catalog must still load so it can be viewed, reported, or deleted;
     * see PermissionCatalog and Docs/DIARIO_DECISIONES_IA.md.
     */
    public static function reconstitute(int $id, string $module, string $action): self
    {
        return new self(id: $id, module: $module, action: $action);
    }

    /**
     * A persisted permission's module/action is its technical identity —
     * Policies and RoleSeeder reference it by name. Renaming it here
     * would silently orphan those references, so any actual change is
     * refused; saving the unchanged pair is a harmless no-op.
     */
    public function redefine(string $module, string $action): void
    {
        if ($module !== $this->module || $action !== $this->action) {
            throw PermissionIsProtectedException::forName($this->name());
        }
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function module(): string
    {
        return $this->module;
    }

    public function action(): string
    {
        return $this->action;
    }

    public function name(): string
    {
        return "{$this->module}.{$this->action}";
    }
}
