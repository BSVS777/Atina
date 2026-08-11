<?php

declare(strict_types=1);

namespace Src\Academic\TeacherAssignment\Domain\Entities;

use Src\Academic\TeacherAssignment\Domain\ProposalStatus;

/**
 * A teacher proposed for a course group (DO-02a). The status here is
 * deliberately coarse (Proposed/Confirmed/Rejected, matching the
 * professor-provided asignaciones_docentes.estado) — the detailed "why"
 * lives in this assignment's AffinityVerification trail.
 */
final class TeacherAssignment
{
    public function __construct(
        private readonly ?int $id,
        private readonly int $courseGroupId,
        private readonly int $teacherId,
        private ProposalStatus $status,
    ) {}

    public function id(): ?int
    {
        return $this->id;
    }

    public function courseGroupId(): int
    {
        return $this->courseGroupId;
    }

    public function teacherId(): int
    {
        return $this->teacherId;
    }

    public function status(): ProposalStatus
    {
        return $this->status;
    }

    public function confirm(): void
    {
        $this->status = ProposalStatus::Confirmed;
    }

    public function reject(): void
    {
        $this->status = ProposalStatus::Rejected;
    }

    public function isDecided(): bool
    {
        return $this->status !== ProposalStatus::Proposed;
    }

    public function withId(int $id): self
    {
        return new self($id, $this->courseGroupId, $this->teacherId, $this->status);
    }
}
