<?php

declare(strict_types=1);

namespace Src\Academic\TeacherAssignment\Presentation\Policies;

use App\Models\User;

/**
 * DO-02a/DO-02d: proposing a teacher (which triggers the automatic
 * verification) and deciding a "Sin catálogo" case manually are both
 * part of what the official `atinencia.verificar` permission covers,
 * granted to Administrador and Coordinadora de Docencia — see
 * Docs/DIARIO_DECISIONES_IA.md. Superadmin bypasses via Gate::before.
 */
class TeacherAssignmentPolicy
{
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('atinencia.verificar');
    }

    public function decide(User $user): bool
    {
        return $user->hasPermissionTo('atinencia.verificar');
    }

    /**
     * Correcting a misclicked teacher/course-group is part of the same
     * verification workflow as proposing one — see
     * EditTeacherAssignmentUseCase.
     */
    public function update(User $user): bool
    {
        return $user->hasPermissionTo('atinencia.verificar');
    }

    /**
     * Removing an accidental proposal is likewise part of
     * `atinencia.verificar` — DeleteTeacherAssignmentUseCase still blocks
     * the operation server-side once formal history depends on the
     * record, regardless of this permission.
     */
    public function delete(User $user): bool
    {
        return $user->hasPermissionTo('atinencia.verificar');
    }

    public function exportPdf(User $user): bool
    {
        return $user->hasPermissionTo('atinencia.verificar');
    }

    public function exportExcel(User $user): bool
    {
        return $user->hasPermissionTo('atinencia.verificar');
    }
}
