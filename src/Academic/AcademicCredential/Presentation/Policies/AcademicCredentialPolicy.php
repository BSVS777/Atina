<?php

declare(strict_types=1);

namespace Src\Academic\AcademicCredential\Presentation\Policies;

use App\Models\User;

/**
 * Registered via Gate::policy() in DomainServiceProvider::$domainPolicies.
 * Superadmin bypasses all of this through Gate::before.
 *
 * Gated on the single official `atestados.gestionar` permission (matches
 * the professor-provided database, which does not split create/edit) —
 * see Docs/DIARIO_DECISIONES_IA.md.
 *
 * No delete method: this module only supports create/edit by design (the
 * source requirement DO-01 explicitly excludes deletion).
 */
class AcademicCredentialPolicy
{
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('atestados.gestionar');
    }

    public function update(User $user): bool
    {
        return $user->hasPermissionTo('atestados.gestionar');
    }
}
