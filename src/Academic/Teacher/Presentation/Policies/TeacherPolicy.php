<?php

declare(strict_types=1);

namespace Src\Academic\Teacher\Presentation\Policies;

use App\Models\User;

/**
 * Registered via Gate::policy() in DomainServiceProvider::$domainPolicies.
 * Superadmin bypasses all of this through Gate::before.
 *
 * Gated on the official `usuarios.gestionar` permission — creating a
 * teacher record is user/staff administration, not an academic-content
 * action, so it reuses the same permission as the rest of user management
 * instead of introducing a teacher-specific one.
 *
 * No update/delete method: the Docentes screen only supports create by
 * design (see TeacherComponent's doc comment) — edit/delete stay out of
 * scope.
 */
class TeacherPolicy
{
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('usuarios.gestionar');
    }

    public function exportPdf(User $user): bool
    {
        return $user->hasPermissionTo('usuarios.gestionar');
    }

    public function exportExcel(User $user): bool
    {
        return $user->hasPermissionTo('usuarios.gestionar');
    }
}
