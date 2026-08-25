<?php

namespace Tests\Unit\IdentityAccess\Fakes;

use Src\IdentityAccess\Permission\Domain\Contracts\PermissionRepositoryInterface;
use Src\IdentityAccess\Permission\Domain\Entities\Permission;

final class InMemoryPermissionRepository implements PermissionRepositoryInterface
{
    /** @var array<int, Permission> */
    private array $permissions = [];

    private int $nextId = 1;

    public function find(int $id): ?Permission
    {
        return $this->permissions[$id] ?? null;
    }

    public function all(?string $search = null, ?string $sortBy = null, string $sortDir = 'asc'): array
    {
        return array_values($this->permissions);
    }

    public function paginate(?string $search, ?string $module, int $perPage, int $page, ?string $sortBy = null, string $sortDir = 'asc'): array
    {
        $items = $this->all();

        return ['items' => array_slice($items, ($page - 1) * $perPage, $perPage), 'total' => count($items)];
    }

    public function save(Permission $permission): Permission
    {
        $id = $permission->id() ?? $this->nextId++;
        $saved = $permission->id() === null
            ? Permission::reconstitute($id, $permission->module(), $permission->action())
            : $permission;
        $this->permissions[$id] = $saved;

        return $saved;
    }

    public function delete(int $id): void
    {
        unset($this->permissions[$id]);
    }
}
