<?php

declare(strict_types=1);

namespace Src\Academic\TeacherAssignment\Application\UseCases;

use DateTimeImmutable;
use Src\Academic\TeacherAssignment\Application\DTOs\AttachTechnicalNoteDTO;
use Src\Academic\TeacherAssignment\Domain\Contracts\AffinityVerificationRepositoryInterface;
use Src\Academic\TeacherAssignment\Domain\Contracts\TechnicalNoteRepositoryInterface;
use Src\Academic\TeacherAssignment\Domain\Entities\AffinityVerification;
use Src\Academic\TeacherAssignment\Domain\Entities\TechnicalNote;
use Src\Academic\TeacherAssignment\Domain\Exceptions\InvalidAssignmentTransitionException;
use Src\Academic\TeacherAssignment\Domain\Exceptions\InvalidTechnicalNoteAttachmentException;
use Src\Academic\TeacherAssignment\Domain\Exceptions\InvalidTechnicalNoteDeadlineException;
use Src\Academic\TeacherAssignment\Domain\TechnicalNoteStatus;
use Src\Academic\TeacherAssignment\Domain\VerificationResult;
use Src\Shared\Audit\Domain\Contracts\AuditLogRepositoryInterface;
use Src\Shared\Audit\Domain\Entities\AuditLogEntry;

/**
 * DO-02b: the Coordinadora de Docencia registers a provisional assignment
 * for a "No Atinente" or "Sin catálogo" result, attaching the signed
 * technical criterion. The original verification is never overwritten
 * (D12) — this appends a new "Nota técnica" verification event instead.
 */
final class AttachTechnicalNoteUseCase
{
    public const AUDITABLE_TYPE = 'technical_note';

    private const REQUIRED_MIME_TYPE = 'application/pdf';

    public function __construct(
        private readonly TechnicalNoteRepositoryInterface $notes,
        private readonly AffinityVerificationRepositoryInterface $verifications,
        private readonly AuditLogRepositoryInterface $auditLog,
    ) {}

    public function handle(AttachTechnicalNoteDTO $dto, ?int $actorUserId): TechnicalNote
    {
        $latest = $this->verifications->latestForAssignment($dto->teacherAssignmentId);

        if ($latest === null || ! in_array($latest->result(), [VerificationResult::NotMatched, VerificationResult::NoCatalog], true)) {
            throw InvalidAssignmentTransitionException::technicalNoteRequiresNotMatchedOrNoCatalog();
        }

        if ($this->notes->forAssignment($dto->teacherAssignmentId) !== null) {
            throw InvalidAssignmentTransitionException::technicalNoteAlreadyExists();
        }

        if ($dto->document->mimeType !== self::REQUIRED_MIME_TYPE) {
            throw InvalidTechnicalNoteAttachmentException::mustBeAPdf($dto->document->mimeType);
        }

        $ratificationDeadline = $this->parseDeadline($dto->ratificationDeadline);

        $note = new TechnicalNote(
            id: null,
            teacherAssignmentId: $dto->teacherAssignmentId,
            documentPath: $dto->document->storagePath,
            submittedByUserId: $actorUserId,
            ratificationDeadline: $ratificationDeadline,
            status: TechnicalNoteStatus::PendingRatification,
            ratifiedAt: null,
        );
        $savedNote = $this->notes->save($note, $dto->document);
        $noteId = $savedNote->id() ?? throw new \LogicException('The repository must return the saved technical note with an id.');

        $this->verifications->save(new AffinityVerification(
            id: null,
            teacherAssignmentId: $dto->teacherAssignmentId,
            catalogVersionId: $latest->catalogVersionId(),
            matchedCredentialId: null,
            performedByUserId: $actorUserId,
            result: VerificationResult::TechnicalNote,
            isProvisional: false,
            justification: null,
            performedAt: new DateTimeImmutable,
        ));

        $this->auditLog->record(new AuditLogEntry(
            actorUserId: $actorUserId,
            auditableType: self::AUDITABLE_TYPE,
            auditableId: $noteId,
            action: AuditLogEntry::ACTION_CREATED,
            changes: [
                'teacher_assignment_id' => ['before' => null, 'after' => $dto->teacherAssignmentId],
                'ratification_deadline' => ['before' => null, 'after' => $dto->ratificationDeadline],
                'document' => ['before' => null, 'after' => $dto->document->originalFileName],
            ],
        ));

        return $savedNote;
    }

    private function parseDeadline(string $ratificationDeadline): DateTimeImmutable
    {
        if (trim($ratificationDeadline) === '') {
            throw InvalidTechnicalNoteDeadlineException::required();
        }

        try {
            $deadline = new DateTimeImmutable($ratificationDeadline);
        } catch (\Exception) {
            throw InvalidTechnicalNoteDeadlineException::required();
        }

        if ($deadline < new DateTimeImmutable('today')) {
            throw InvalidTechnicalNoteDeadlineException::mustNotBeInThePast();
        }

        return $deadline;
    }
}
