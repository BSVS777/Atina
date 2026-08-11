<?php

declare(strict_types=1);

namespace Src\Academic\TeacherAssignment\Presentation\Policies;

use App\Models\User;

/**
 * DO-02b: registering a Technical Note is part of `atinencia.verificar`
 * (Administrador + Coordinadora de Docencia). Ratifying/rejecting it —
 * the Consejo Universitario's decision (D13) — is gated on the stricter
 * `nota_tecnica.aprobar`, granted to Administrador only in the
 * professor-provided database. See Docs/DIARIO_DECISIONES_IA.md.
 */
class TechnicalNotePolicy
{
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('atinencia.verificar');
    }

    public function approve(User $user): bool
    {
        return $user->hasPermissionTo('nota_tecnica.aprobar');
    }
}
