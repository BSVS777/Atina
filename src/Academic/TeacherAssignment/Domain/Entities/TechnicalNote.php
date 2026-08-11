<?php

declare(strict_types=1);

namespace Src\Academic\TeacherAssignment\Domain\Entities;

use DateTimeImmutable;
use InvalidArgumentException;
use Src\Academic\TeacherAssignment\Domain\TechnicalNoteStatus;

/**
 * The exceptional "experience-proven affinity" path (DO-02b). One per
 * TeacherAssignment (the source requirement's unique constraint).
 */
final class TechnicalNote
{
    public function __construct(
        private readonly ?int $id,
        private readonly int $teacherAssignmentId,
        private readonly string $documentPath,
        private readonly ?int $submittedByUserId,
        private readonly DateTimeImmutable $ratificationDeadline,
        private TechnicalNoteStatus $status,
        private ?DateTimeImmutable $ratifiedAt,
    ) {
        if (trim($documentPath) === '') {
            throw new InvalidArgumentException('Debe adjuntar el criterio técnico firmado que fundamenta la Nota técnica.');
        }
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function teacherAssignmentId(): int
    {
        return $this->teacherAssignmentId;
    }

    public function documentPath(): string
    {
        return $this->documentPath;
    }

    public function submittedByUserId(): ?int
    {
        return $this->submittedByUserId;
    }

    public function ratificationDeadline(): DateTimeImmutable
    {
        return $this->ratificationDeadline;
    }

    public function status(): TechnicalNoteStatus
    {
        return $this->status;
    }

    public function ratifiedAt(): ?DateTimeImmutable
    {
        return $this->ratifiedAt;
    }

    public function ratify(DateTimeImmutable $at): void
    {
        $this->status = TechnicalNoteStatus::Ratified;
        $this->ratifiedAt = $at;
    }

    public function reject(): void
    {
        $this->status = TechnicalNoteStatus::Rejected;
    }

    public function isOverdue(DateTimeImmutable $now): bool
    {
        return $this->status === TechnicalNoteStatus::PendingRatification && $this->ratificationDeadline < $now;
    }

    public function expire(): void
    {
        $this->status = TechnicalNoteStatus::Expired;
    }

    public function withId(int $id): self
    {
        return new self($id, $this->teacherAssignmentId, $this->documentPath, $this->submittedByUserId, $this->ratificationDeadline, $this->status, $this->ratifiedAt);
    }
}
