<?php

declare(strict_types=1);

namespace Src\Academic\TeacherAssignment\Domain\Entities;

use DateTimeImmutable;
use Src\Academic\TeacherAssignment\Domain\VerificationResult;

/**
 * One immutable verification event in a TeacherAssignment's trail
 * (DO-02a). Never updated after creation — escalating to Nota Técnica or
 * deciding a "Sin catálogo" case appends a NEW row instead of mutating
 * an earlier one, so the original result is never overwritten (D11/D12,
 * Docs/DIARIO_DECISIONES_IA.md).
 */
final class AffinityVerification
{
    public function __construct(
        private readonly ?int $id,
        private readonly int $teacherAssignmentId,
        private readonly ?int $catalogVersionId,
        private readonly ?int $matchedCredentialId,
        private readonly ?int $performedByUserId,
        private readonly VerificationResult $result,
        private readonly bool $isProvisional,
        private readonly ?string $justification,
        private readonly DateTimeImmutable $performedAt,
    ) {}

    public function id(): ?int
    {
        return $this->id;
    }

    public function teacherAssignmentId(): int
    {
        return $this->teacherAssignmentId;
    }

    public function catalogVersionId(): ?int
    {
        return $this->catalogVersionId;
    }

    public function matchedCredentialId(): ?int
    {
        return $this->matchedCredentialId;
    }

    public function performedByUserId(): ?int
    {
        return $this->performedByUserId;
    }

    public function result(): VerificationResult
    {
        return $this->result;
    }

    public function isProvisional(): bool
    {
        return $this->isProvisional;
    }

    public function justification(): ?string
    {
        return $this->justification;
    }

    public function performedAt(): DateTimeImmutable
    {
        return $this->performedAt;
    }

    public function withId(int $id): self
    {
        return new self(
            $id,
            $this->teacherAssignmentId,
            $this->catalogVersionId,
            $this->matchedCredentialId,
            $this->performedByUserId,
            $this->result,
            $this->isProvisional,
            $this->justification,
            $this->performedAt,
        );
    }
}
