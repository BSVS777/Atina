<?php

declare(strict_types=1);

namespace Src\Academic\AcademicCredential\Presentation\Policies;

use App\Models\User;

/**
 * Registered via Gate::policy() in DomainServiceProvider::$domainPolicies.
 * Superadmin bypasses all of this through Gate::before.
 *
 * No delete method: this module only supports create/edit by design (the
 * source requirement this was ported from explicitly excludes deletion).
 */
class AcademicCredentialPolicy
{
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('academic_credentials.create');
    }

    public function update(User $user): bool
    {
        return $user->hasPermissionTo('academic_credentials.edit');
    }
}
