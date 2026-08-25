<?php

namespace Tests\Unit\IdentityAccess;

use PHPUnit\Framework\TestCase;
use Src\IdentityAccess\Permission\Application\DTOs\PermissionDTO;
use Src\IdentityAccess\Permission\Application\UseCases\CreatePermissionUseCase;
use Src\IdentityAccess\Permission\Application\UseCases\DeletePermissionUseCase;
use Src\IdentityAccess\Permission\Application\UseCases\GetPermissionCatalogStatusUseCase;
use Src\IdentityAccess\Permission\Application\UseCases\UpdatePermissionUseCase;
use Src\IdentityAccess\Permission\Domain\Entities\Permission;
use Src\IdentityAccess\Permission\Domain\Exceptions\InvalidPermissionException;
use Src\IdentityAccess\Permission\Domain\Exceptions\PermissionIsProtectedException;
use Src\IdentityAccess\Permission\Domain\Exceptions\PermissionNotFoundException;
use Tests\Unit\IdentityAccess\Fakes\InMemoryPermissionRepository;

/**
 * The RBAC write path over an in-memory repository — the closed-catalog
 * and protection rules must hold wherever these use cases are invoked
 * from, not only behind the Livewire form.
 */
class PermissionUseCasesTest extends TestCase
{
    private InMemoryPermissionRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new InMemoryPermissionRepository;
    }

    public function test_creating_an_official_permission_persists_it_with_its_technical_name(): void
    {
        $permission = (new CreatePermissionUseCase($this->repository))->handle(new PermissionDTO('atinencia', 'verificar'));

        $this->assertSame('atinencia.verificar', $permission->name());
        $this->assertNotNull($permission->id());
    }

    public function test_creating_a_permission_outside_the_catalog_is_refused_before_persistence(): void
    {
        try {
            (new CreatePermissionUseCase($this->repository))->handle(new PermissionDTO('atinencia', 'delete_everything'));
            $this->fail('An unofficial combination must not be creatable.');
        } catch (InvalidPermissionException) {
            $this->assertSame([], $this->repository->all());
        }
    }

    public function test_an_official_permission_cannot_be_deleted(): void
    {
        $official = $this->repository->save(Permission::create('atinencia', 'verificar'));

        $this->expectException(PermissionIsProtectedException::class);

        (new DeletePermissionUseCase($this->repository))->handle($official->id());
    }

    public function test_a_legacy_permission_outside_the_catalog_can_be_deleted(): void
    {
        $legacy = $this->repository->save(Permission::reconstitute(1, 'legado', 'custom_action'));

        (new DeletePermissionUseCase($this->repository))->handle($legacy->id());

        $this->assertNull($this->repository->find($legacy->id()));
    }

    public function test_deleting_a_permission_that_does_not_exist_is_refused(): void
    {
        $this->expectException(PermissionNotFoundException::class);

        (new DeletePermissionUseCase($this->repository))->handle(404);
    }

    public function test_renaming_a_persisted_permission_is_refused(): void
    {
        $permission = $this->repository->save(Permission::create('atinencia', 'verificar'));

        $this->expectException(PermissionIsProtectedException::class);

        (new UpdatePermissionUseCase($this->repository))->handle($permission->id(), new PermissionDTO('roles', 'edit'));
    }

    public function test_saving_a_permission_under_its_own_module_and_action_is_a_no_op(): void
    {
        $permission = $this->repository->save(Permission::create('atinencia', 'verificar'));

        $saved = (new UpdatePermissionUseCase($this->repository))->handle($permission->id(), new PermissionDTO('atinencia', 'verificar'));

        $this->assertSame('atinencia.verificar', $saved->name());
    }

    public function test_updating_a_permission_that_does_not_exist_is_refused(): void
    {
        $this->expectException(PermissionNotFoundException::class);

        (new UpdatePermissionUseCase($this->repository))->handle(404, new PermissionDTO('atinencia', 'verificar'));
    }

    public function test_the_catalog_status_reflects_what_the_repository_already_holds(): void
    {
        $this->repository->save(Permission::create('atinencia', 'verificar'));

        $status = (new GetPermissionCatalogStatusUseCase($this->repository))->handle();

        $this->assertFalse($status->isComplete());
        $this->assertSame(1, $status->registeredCount());
        $this->assertNotContains('atinencia', $status->availableModules());
    }
}
