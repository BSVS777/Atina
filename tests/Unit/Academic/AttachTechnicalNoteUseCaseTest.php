<?php

namespace Tests\Unit\Academic;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Src\Academic\TeacherAssignment\Application\DTOs\AttachTechnicalNoteDTO;
use Src\Academic\TeacherAssignment\Application\UseCases\AttachTechnicalNoteUseCase;
use Src\Academic\TeacherAssignment\Domain\Contracts\UploadedDocument;
use Src\Academic\TeacherAssignment\Domain\Entities\AffinityVerification;
use Src\Academic\TeacherAssignment\Domain\Exceptions\InvalidAssignmentTransitionException;
use Src\Academic\TeacherAssignment\Domain\Exceptions\InvalidTechnicalNoteAttachmentException;
use Src\Academic\TeacherAssignment\Domain\Exceptions\InvalidTechnicalNoteDeadlineException;
use Src\Academic\TeacherAssignment\Domain\TechnicalNoteStatus;
use Src\Academic\TeacherAssignment\Domain\VerificationResult;
use Src\Shared\Audit\Domain\Entities\AuditLogEntry;
use Tests\Unit\Academic\Fakes\InMemoryAffinityVerificationRepository;
use Tests\Unit\Academic\Fakes\InMemoryAuditLogRepository;
use Tests\Unit\Academic\Fakes\InMemoryTechnicalNoteRepository;

/**
 * DO-02b's three attachment invariants, enforced below Presentation:
 * the source verification must be "No Atinente" or "Sin catálogo", the
 * criterion document must be a signed PDF, and the ratification deadline
 * must exist and not be in the past.
 *
 * The deadline rule is compared against the calendar day the use case
 * itself reads (`new DateTimeImmutable('today')`), so these tests derive
 * their inputs from that same anchor instead of hardcoding a date that
 * would silently rot.
 */
class AttachTechnicalNoteUseCaseTest extends TestCase
{
    private const ASSIGNMENT_ID = 1;

    private InMemoryTechnicalNoteRepository $notes;

    private InMemoryAffinityVerificationRepository $verifications;

    private InMemoryAuditLogRepository $auditLog;

    private AttachTechnicalNoteUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->notes = new InMemoryTechnicalNoteRepository;
        $this->verifications = new InMemoryAffinityVerificationRepository;
        $this->auditLog = new InMemoryAuditLogRepository;
        $this->useCase = new AttachTechnicalNoteUseCase($this->notes, $this->verifications, $this->auditLog);
    }

    public function test_a_signed_pdf_on_a_no_atinente_assignment_creates_a_note_pending_ratification(): void
    {
        $this->recordVerification(VerificationResult::NotMatched, catalogVersionId: 5);

        $note = $this->useCase->handle($this->dto(), actorUserId: 3);

        $this->assertSame(TechnicalNoteStatus::PendingRatification, $note->status());
        $this->assertSame(self::ASSIGNMENT_ID, $note->teacherAssignmentId());
        $this->assertSame(3, $note->submittedByUserId());
        $this->assertNull($note->ratifiedAt());
    }

    public function test_a_sin_catalogo_assignment_may_also_escalate_to_a_technical_note(): void
    {
        $this->recordVerification(VerificationResult::NoCatalog, catalogVersionId: null);

        $note = $this->useCase->handle($this->dto(), actorUserId: null);

        $this->assertSame(TechnicalNoteStatus::PendingRatification, $note->status());
    }

    public function test_the_original_verification_is_preserved_and_a_new_technical_note_event_is_appended(): void
    {
        $this->recordVerification(VerificationResult::NotMatched, catalogVersionId: 5);

        $this->useCase->handle($this->dto(), actorUserId: null);

        $trail = $this->verifications->forAssignment(self::ASSIGNMENT_ID);

        $this->assertCount(2, $trail);
        $this->assertSame(VerificationResult::NotMatched, $trail[0]->result());
        $this->assertSame(VerificationResult::TechnicalNote, $trail[1]->result());
        $this->assertSame(5, $trail[1]->catalogVersionId(), 'The escalation must keep citing the same catalog version.');
        $this->assertFalse($trail[1]->isProvisional());
    }

    public function test_attaching_a_note_records_a_creation_audit_entry(): void
    {
        $this->recordVerification(VerificationResult::NotMatched, catalogVersionId: null);

        $note = $this->useCase->handle($this->dto(), actorUserId: 9);

        $entries = $this->auditLog->entries();
        $this->assertCount(1, $entries);
        $this->assertSame(AuditLogEntry::ACTION_CREATED, $entries[0]->action());
        $this->assertSame(AttachTechnicalNoteUseCase::AUDITABLE_TYPE, $entries[0]->auditableType());
        $this->assertSame($note->id(), $entries[0]->auditableId());
        $this->assertSame(9, $entries[0]->actorUserId());
    }

    public function test_an_atinente_assignment_cannot_escalate_to_a_technical_note(): void
    {
        $this->recordVerification(VerificationResult::Matched, catalogVersionId: 5);

        $this->expectException(InvalidAssignmentTransitionException::class);

        $this->useCase->handle($this->dto(), actorUserId: null);
    }

    public function test_an_assignment_whose_latest_event_is_already_a_technical_note_cannot_escalate_again(): void
    {
        $this->recordVerification(VerificationResult::TechnicalNote, catalogVersionId: 5);

        $this->expectException(InvalidAssignmentTransitionException::class);

        $this->useCase->handle($this->dto(), actorUserId: null);
    }

    public function test_an_assignment_without_any_verification_cannot_escalate(): void
    {
        $this->expectException(InvalidAssignmentTransitionException::class);

        $this->useCase->handle($this->dto(), actorUserId: null);
    }

    public function test_an_assignment_that_already_has_a_technical_note_cannot_receive_a_second_one(): void
    {
        $this->recordVerification(VerificationResult::NotMatched, catalogVersionId: null);
        $this->useCase->handle($this->dto(), actorUserId: null);
        // The escalation left "No Atinente" as an earlier event; make the
        // latest one eligible again so the duplicate guard is what fires.
        $this->recordVerification(VerificationResult::NotMatched, catalogVersionId: null);

        $this->expectException(InvalidAssignmentTransitionException::class);

        $this->useCase->handle($this->dto(), actorUserId: null);
    }

    #[DataProvider('nonPdfMimeTypes')]
    public function test_a_document_that_is_not_a_pdf_is_rejected(string $mimeType): void
    {
        $this->recordVerification(VerificationResult::NotMatched, catalogVersionId: null);

        $this->expectException(InvalidTechnicalNoteAttachmentException::class);

        $this->useCase->handle($this->dto(mimeType: $mimeType), actorUserId: null);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function nonPdfMimeTypes(): array
    {
        return [
            'plain text' => ['text/plain'],
            'image' => ['image/png'],
            'opaque binary' => ['application/octet-stream'],
            'word document' => ['application/msword'],
            'missing mime type' => [''],
            'pdf-looking but wrong' => ['application/x-pdf'],
        ];
    }

    public function test_no_note_is_persisted_when_the_attachment_is_rejected(): void
    {
        $this->recordVerification(VerificationResult::NotMatched, catalogVersionId: null);

        try {
            $this->useCase->handle($this->dto(mimeType: 'image/png'), actorUserId: null);
        } catch (InvalidTechnicalNoteAttachmentException) {
            // Expected — the assertions below are the point of this test.
        }

        $this->assertNull($this->notes->forAssignment(self::ASSIGNMENT_ID));
        $this->assertSame([], $this->auditLog->entries());
        $this->assertCount(1, $this->verifications->forAssignment(self::ASSIGNMENT_ID));
    }

    public function test_a_deadline_falling_today_is_accepted(): void
    {
        $this->recordVerification(VerificationResult::NotMatched, catalogVersionId: null);

        $note = $this->useCase->handle($this->dto(deadline: $this->daysFromToday(0)), actorUserId: null);

        $this->assertSame($this->daysFromToday(0), $note->ratificationDeadline()->format('Y-m-d'));
    }

    public function test_a_future_deadline_is_accepted(): void
    {
        $this->recordVerification(VerificationResult::NotMatched, catalogVersionId: null);

        $note = $this->useCase->handle($this->dto(deadline: $this->daysFromToday(30)), actorUserId: null);

        $this->assertSame($this->daysFromToday(30), $note->ratificationDeadline()->format('Y-m-d'));
    }

    public function test_a_deadline_that_already_passed_is_rejected(): void
    {
        $this->recordVerification(VerificationResult::NotMatched, catalogVersionId: null);

        $this->expectException(InvalidTechnicalNoteDeadlineException::class);

        $this->useCase->handle($this->dto(deadline: $this->daysFromToday(-1)), actorUserId: null);
    }

    public function test_a_missing_deadline_is_rejected(): void
    {
        $this->recordVerification(VerificationResult::NotMatched, catalogVersionId: null);

        $this->expectException(InvalidTechnicalNoteDeadlineException::class);

        $this->useCase->handle($this->dto(deadline: '   '), actorUserId: null);
    }

    public function test_an_unparseable_deadline_is_rejected(): void
    {
        $this->recordVerification(VerificationResult::NotMatched, catalogVersionId: null);

        $this->expectException(InvalidTechnicalNoteDeadlineException::class);

        $this->useCase->handle($this->dto(deadline: 'no-es-una-fecha'), actorUserId: null);
    }

    private function daysFromToday(int $days): string
    {
        $today = new DateTimeImmutable('today');

        return ($days >= 0 ? $today->modify("+{$days} days") : $today->modify("{$days} days"))->format('Y-m-d');
    }

    private function recordVerification(VerificationResult $result, ?int $catalogVersionId): void
    {
        $this->verifications->save(new AffinityVerification(
            id: null,
            teacherAssignmentId: self::ASSIGNMENT_ID,
            catalogVersionId: $catalogVersionId,
            matchedCredentialId: null,
            performedByUserId: null,
            result: $result,
            isProvisional: false,
            justification: null,
            performedAt: new DateTimeImmutable('2026-05-01'),
        ));
    }

    private function dto(string $mimeType = 'application/pdf', ?string $deadline = null): AttachTechnicalNoteDTO
    {
        return new AttachTechnicalNoteDTO(
            teacherAssignmentId: self::ASSIGNMENT_ID,
            ratificationDeadline: $deadline ?? $this->daysFromToday(30),
            document: new UploadedDocument(
                storagePath: 'technical-notes/criterion.pdf',
                originalFileName: 'criterion.pdf',
                mimeType: $mimeType,
                sizeBytes: 2048,
                hashSha256: str_repeat('a', 64),
            ),
        );
    }
}
