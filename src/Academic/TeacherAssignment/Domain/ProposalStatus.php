<?php

declare(strict_types=1);

namespace Src\Academic\TeacherAssignment\Domain;

/**
 * Mirrors asignaciones_docentes.estado — the professor-provided database
 * only has these three values (no separate "Blocked"/"PendingManual"
 * column). "Blocked" and "pending manual approval" are read off the
 * combination of this status (still Proposed) and the latest
 * AffinityVerification result — see Docs/DIARIO_DECISIONES_IA.md.
 */
enum ProposalStatus: string
{
    case Proposed = 'proposed';
    case Confirmed = 'confirmed';
    case Rejected = 'rejected';
}
