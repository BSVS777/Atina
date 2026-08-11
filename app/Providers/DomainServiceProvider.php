<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Src\Academic\AcademicCredential\Domain\Contracts\AcademicCredentialRepositoryInterface;
use Src\Academic\AcademicCredential\Domain\Entities\AcademicCredential;
use Src\Academic\AcademicCredential\Infrastructure\Persistence\Repositories\EloquentAcademicCredentialRepository;
use Src\Academic\AcademicCredential\Presentation\Policies\AcademicCredentialPolicy;
use Src\Academic\AffinityCatalog\Domain\Contracts\AffinityCatalogVersionRepositoryInterface;
use Src\Academic\AffinityCatalog\Domain\Entities\AffinityCatalogVersion;
use Src\Academic\AffinityCatalog\Infrastructure\Persistence\Repositories\EloquentAffinityCatalogVersionRepository;
use Src\Academic\AffinityCatalog\Presentation\Policies\AffinityCatalogVersionPolicy;
use Src\Academic\TeacherAssignment\Domain\Contracts\AffinityVerificationRepositoryInterface;
use Src\Academic\TeacherAssignment\Domain\Contracts\TeacherAssignmentRepositoryInterface;
use Src\Academic\TeacherAssignment\Domain\Contracts\TechnicalNoteRepositoryInterface;
use Src\Academic\TeacherAssignment\Domain\Entities\TeacherAssignment;
use Src\Academic\TeacherAssignment\Domain\Entities\TechnicalNote;
use Src\Academic\TeacherAssignment\Infrastructure\Persistence\Repositories\EloquentAffinityVerificationRepository;
use Src\Academic\TeacherAssignment\Infrastructure\Persistence\Repositories\EloquentTeacherAssignmentRepository;
use Src\Academic\TeacherAssignment\Infrastructure\Persistence\Repositories\EloquentTechnicalNoteRepository;
use Src\Academic\TeacherAssignment\Presentation\Policies\TeacherAssignmentPolicy;
use Src\Academic\TeacherAssignment\Presentation\Policies\TechnicalNotePolicy;
use Src\IdentityAccess\Permission\Domain\Contracts\PermissionRepositoryInterface;
use Src\IdentityAccess\Permission\Domain\Entities\Permission;
use Src\IdentityAccess\Permission\Infrastructure\Persistence\Repositories\EloquentPermissionRepository;
use Src\IdentityAccess\Permission\Presentation\Policies\PermissionPolicy;
use Src\IdentityAccess\Role\Domain\Contracts\RoleRepositoryInterface;
use Src\IdentityAccess\Role\Domain\Entities\Role;
use Src\IdentityAccess\Role\Infrastructure\Persistence\Repositories\EloquentRoleRepository;
use Src\IdentityAccess\Role\Presentation\Policies\RolePolicy;
use Src\Shared\Audit\Domain\Contracts\AuditLogRepositoryInterface;
use Src\Shared\Audit\Infrastructure\Persistence\Repositories\EloquentAuditLogRepository;
use Src\Shared\Export\Contracts\ExcelExporterInterface;
use Src\Shared\Export\Contracts\PdfExporterInterface;
use Src\Shared\Export\Infrastructure\SpatieExcelExporter;
use Src\Shared\Export\Infrastructure\SpatiePdfExporter;

final class DomainServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    private array $domainBindings = [
        RoleRepositoryInterface::class => EloquentRoleRepository::class,
        PermissionRepositoryInterface::class => EloquentPermissionRepository::class,
        ExcelExporterInterface::class => SpatieExcelExporter::class,
        PdfExporterInterface::class => SpatiePdfExporter::class,
        AcademicCredentialRepositoryInterface::class => EloquentAcademicCredentialRepository::class,
        AuditLogRepositoryInterface::class => EloquentAuditLogRepository::class,
        AffinityCatalogVersionRepositoryInterface::class => EloquentAffinityCatalogVersionRepository::class,
        TeacherAssignmentRepositoryInterface::class => EloquentTeacherAssignmentRepository::class,
        AffinityVerificationRepositoryInterface::class => EloquentAffinityVerificationRepository::class,
        TechnicalNoteRepositoryInterface::class => EloquentTechnicalNoteRepository::class,
    ];

    /**
     * @var array<class-string, class-string>
     */
    private array $domainPolicies = [
        Role::class => RolePolicy::class,
        Permission::class => PermissionPolicy::class,
        AcademicCredential::class => AcademicCredentialPolicy::class,
        AffinityCatalogVersion::class => AffinityCatalogVersionPolicy::class,
        TeacherAssignment::class => TeacherAssignmentPolicy::class,
        TechnicalNote::class => TechnicalNotePolicy::class,
    ];

    public function register(): void
    {
        foreach ($this->domainBindings as $interface => $implementation) {
            $this->app->bind($interface, $implementation);
        }
    }

    public function boot(): void
    {
        $this->registerPolicies();
        $this->registerSuperAdminBypass();
        $this->loadContextRoutes();
    }

    private function registerPolicies(): void
    {
        foreach ($this->domainPolicies as $entity => $policy) {
            Gate::policy($entity, $policy);
        }
    }

    /**
     * Superadmin passes every authorization check unconditionally. The
     * RoleSeeder already syncs it every existing permission — this is the
     * safety net that also covers permissions introduced after the last
     * seed run, without needing to re-sync anything.
     */
    private function registerSuperAdminBypass(): void
    {
        Gate::before(function (Authenticatable $user): ?bool {
            return method_exists($user, 'hasRole') && $user->hasRole('Superadmin')
                ? true
                : null;
        });
    }

    private function loadContextRoutes(): void
    {
        if (app()->routesAreCached()) {
            return;
        }

        foreach (File::glob(base_path('src/*/*/Presentation/Routes/web.php')) as $routeFile) {
            $this->loadRoutesFrom($routeFile);
        }
    }
}
