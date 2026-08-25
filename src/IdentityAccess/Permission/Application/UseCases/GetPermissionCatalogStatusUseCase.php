<?php

declare(strict_types=1);

namespace Src\IdentityAccess\Permission\Application\UseCases;

use Src\IdentityAccess\Permission\Domain\Contracts\PermissionRepositoryInterface;
use Src\IdentityAccess\Permission\Domain\Entities\Permission;
use Src\IdentityAccess\Permission\Domain\ValueObjects\PermissionCatalogStatus;

final class GetPermissionCatalogStatusUseCase
{
    public function __construct(
        private readonly PermissionRepositoryInterface $repository,
    ) {}

    public function handle(): PermissionCatalogStatus
    {
        $registered = array_map(
            static fn (Permission $permission): array => ['module' => $permission->module(), 'action' => $permission->action()],
            $this->repository->all(),
        );

        return PermissionCatalogStatus::fromRegistered($registered);
    }
}
