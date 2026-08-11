<?php

declare(strict_types=1);

namespace Src\Academic\TeacherAssignment\Domain;

/**
 * Mirrors notas_tecnicas.estado (DO-02b). "Expired" is terminal — a
 * teacher/group pair that expires must start a brand new assignment to
 * retry (D14, Docs/DIARIO_DECISIONES_IA.md), not reopen this one.
 */
enum TechnicalNoteStatus: string
{
    case PendingRatification = 'pending_ratification';
    case Ratified = 'ratified';
    case Expired = 'expired';
    case Rejected = 'rejected';
}
