<?php

declare(strict_types=1);

namespace Src\Academic\AffinityCatalog\Presentation\Policies;

use App\Models\User;

/**
 * DO-02: "Solo el Administrador actualiza el catálogo." Gated on the
 * official `catalogo.gestionar` permission, granted only to
 * "Administrador" in the professor-provided database — see
 * Docs/DIARIO_DECISIONES_IA.md. Superadmin bypasses via Gate::before.
 *
 * No update/delete method: every change is a brand new version (DO-02),
 * never a mutation of an existing one.
 */
class AffinityCatalogVersionPolicy
{
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('catalogo.gestionar');
    }

    public function exportPdf(User $user): bool
    {
        return $user->hasPermissionTo('catalogo.gestionar');
    }

    public function exportExcel(User $user): bool
    {
        return $user->hasPermissionTo('catalogo.gestionar');
    }
}
