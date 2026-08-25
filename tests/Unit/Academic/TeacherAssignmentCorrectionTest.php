<?php

namespace Tests\Unit\Academic;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Src\Academic\AcademicCredential\Application\DTOs\AcademicCredentialDTO;
use Src\Academic\AcademicCredential\Application\UseCases\RegisterAcademicCredentialUseCase;
use Src\Academic\AcademicCredential\Domain\DegreeLevel;
use Src\Academic\AffinityCatalog\Application\UseCases\ResolveApplicableCatalogVersionUseCase;
use Src\Academic\AffinityCatalog\Domain\Entities\AffinityCatalogVersion;
use Src\Academic\AffinityCatalog\Domain\Services\CatalogVersionResolver;
use Src\Academic\TeacherAssignment\Application\DTOs\AssignmentOverview;
use Src\Academic\TeacherAssignment\Application\DTOs\ProposeTeacherAssignmentDTO;
use Src\Academic\TeacherAssignment\Application\UseCases\DeleteTeacherAssignmentUseCase;
use Src\Academic\TeacherAssignment\Application\UseCases\EditTeacherAssignmentUseCase;
use Src\Academic\TeacherAssignment\Application\UseCases\RunAffinityVerificationUseCase;
use Src\Academic\TeacherAssignment\Domain\Entities\AffinityVerification;
use Src\Academic\TeacherAssignment\Domain\Entities\TeacherAssignment;
use Src\Academic\TeacherAssignment\Domain\Entities\TechnicalNote;
use Src\Academic\TeacherAssignment\Domain\Exceptions\InvalidAssignmentTransitionException;
use Src\Academic\TeacherAssignment\Domain\ProposalStatus;
use Src\Academic\TeacherAssignment\Domain\TechnicalNoteStatus;
use Src\Academic\TeacherAssignment\Domain\VerificationResult;
use Src\Shared\Audit\Domain\Entities\AuditLogEntry;
use Tests\Unit\Academic\Fakes\InMemoryAcademicCredentialRepository;
use Tests\Unit\Academic\Fakes\InMemoryAffinityCatalogVersionRepository;
use Tests\Unit\Academic\Fakes\InMemoryAffinityVerificationRepository;
use Tests\Unit\Academic\Fakes\InMemoryAuditLogRepository;
use Tests\Unit\Academic\Fakes\InMemoryTeacherAssignmentRepository;
use Tests\Unit\Academic\Fakes\InMemoryTechnicalNoteRepository;

/**
 * Corrective-UX edit/delete of an accidental proposal. Two rules are
 * under test, both below Livewire: a correction never assigns an
 * affinity result by hand (it reruns the real matching algorithm), and
 * neither correction nor deletion may touch a record that already
 * carries formal history (AssignmentOverview::hasProtectedHistory()).
 */
class TeacherAssignmentCorrectionTest extends TestCase
{
    private const ASSIGNMENT_ID = 1;

    private const COURSE_ID = 1;

    private const AFFINE_SPECIALTY = 10;

    private const TARGET_DATE = '2026-05-01';

    private InMemoryTeacherAssignmentRepository $assignments;

    private InMemoryAffinityVerificationRepository $verifications;

    private InMemoryTechnicalNoteRepository $notes;

    private InMemoryAcademicCredentialRepository $credentials;

    private InMemoryAffinityCatalogVersionRepository $catalog;

    private InMemoryAuditLogRepository $auditLog;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assignments = new InMemoryTeacherAssignmentRepository;
        $this->verifications = new InMemoryAffinityVerificationRepository;
        $this->notes = new InMemoryTechnicalNoteRepository;
        $this->credentials = new InMemoryAcademicCredentialRepository;
        $this->catalog = new InMemoryAffinityCatalogVersionRepository;
        $this->auditLog = new InMemoryAuditLogRepository;

        $this->publishCatalog();
    }

    public function test_an_unprotected_record_has_no_protected_history(): void
    {
        $overview = new AssignmentOverview(
            assignment: new TeacherAssignment(id: 1, courseGroupId: 1, teacherId: 1, status: ProposalStatus::Proposed),
            latestVerification: $this->verification(VerificationResult::NotMatched),
            technicalNote: null,
        );

        $this->assertFalse($overview->hasProtectedHistory());
    }

    public function test_an_auto_confirmed_atinente_record_is_still_correctable(): void
    {
        $overview = new AssignmentOverview(
            assignment: new TeacherAssignment(id: 1, courseGroupId: 1, teacherId: 1, status: ProposalStatus::Confirmed),
            latestVerification: $this->verification(VerificationResult::Matched),
            technicalNote: null,
        );

        $this->assertFalse($overview->hasProtectedHistory());
    }

    public function test_an_undecided_sin_catalogo_record_is_still_correctable(): void
    {
        $overview = new AssignmentOverview(
            assignment: new TeacherAssignment(id: 1, courseGroupId: 1, teacherId: 1, status: ProposalStatus::Proposed),
            latestVerification: $this->verification(VerificationResult::NoCatalog),
            technicalNote: null,
        );

        $this->assertFalse($overview->hasProtectedHistory());
    }

    public function test_a_technical_note_protects_the_record(): void
    {
        $overview = new AssignmentOverview(
            assignment: new TeacherAssignment(id: 1, courseGroupId: 1, teacherId: 1, status: ProposalStatus::Proposed),
            latestVerification: $this->verification(VerificationResult::TechnicalNote),
            technicalNote: $this->technicalNote(),
        );

        $this->assertTrue($overview->hasProtectedHistory());
    }

    public function test_a_decided_sin_catalogo_record_is_protected(): void
    {
        $overview = new AssignmentOverview(
            assignment: new TeacherAssignment(id: 1, courseGroupId: 1, teacherId: 1, status: ProposalStatus::Confirmed),
            latestVerification: $this->verification(VerificationResult::NoCatalog),
            technicalNote: null,
        );

        $this->assertTrue($overview->hasProtectedHistory());
    }

    public function test_correcting_the_teacher_reruns_the_algorithm_instead_of_assigning_the_result(): void
    {
        $this->givenProposal(teacherId: 1, result: VerificationResult::NotMatched);
        $this->giveCredential(teacherId: 2, specialtyId: self::AFFINE_SPECIALTY);

        $result = $this->editUseCase()->handle(self::ASSIGNMENT_ID, $this->dto(teacherId: 2), actorUserId: 6);

        $this->assertSame(VerificationResult::Matched, $result->verification->result());
        $this->assertSame(ProposalStatus::Confirmed, $result->assignment->status());
        $this->assertSame(2, $result->assignment->teacherId());
    }

    public function test_a_correction_appends_a_verification_and_preserves_the_previous_one(): void
    {
        $this->givenProposal(teacherId: 1, result: VerificationResult::NotMatched);
        $this->giveCredential(teacherId: 2, specialtyId: self::AFFINE_SPECIALTY);

        $this->editUseCase()->handle(self::ASSIGNMENT_ID, $this->dto(teacherId: 2), actorUserId: null);

        $trail = $this->verifications->forAssignment(self::ASSIGNMENT_ID);

        $this->assertCount(2, $trail);
        $this->assertSame(VerificationResult::NotMatched, $trail[0]->result());
        $this->assertSame(VerificationResult::Matched, $trail[1]->result());
    }

    public function test_a_correction_that_still_finds_no_match_leaves_the_assignment_undecided(): void
    {
        $this->givenProposal(teacherId: 1, result: VerificationResult::NotMatched);
        $this->giveCredential(teacherId: 2, specialtyId: 99);

        $result = $this->editUseCase()->handle(self::ASSIGNMENT_ID, $this->dto(teacherId: 2), actorUserId: null);

        $this->assertSame(VerificationResult::NotMatched, $result->verification->result());
        $this->assertSame(ProposalStatus::Proposed, $result->assignment->status());
    }

    public function test_a_correction_records_the_recomputed_result_in_the_audit_trail(): void
    {
        $this->givenProposal(teacherId: 1, result: VerificationResult::NotMatched);
        $this->giveCredential(teacherId: 2, specialtyId: self::AFFINE_SPECIALTY);

        $this->editUseCase()->handle(self::ASSIGNMENT_ID, $this->dto(teacherId: 2), actorUserId: 6);

        $entries = $this->auditLog->entries();
        $this->assertCount(1, $entries);
        $this->assertSame(AuditLogEntry::ACTION_UPDATED, $entries[0]->action());
        $this->assertSame(['before' => 1, 'after' => 2], $entries[0]->changes()['teacher_id']);
        $this->assertSame(['before' => 'not_matched', 'after' => 'matched'], $entries[0]->changes()['result']);
    }

    public function test_a_record_with_a_technical_note_cannot_be_corrected(): void
    {
        $this->givenProposal(teacherId: 1, result: VerificationResult::TechnicalNote);
        $this->notes->save($this->technicalNote());

        $this->expectException(InvalidAssignmentTransitionException::class);

        $this->editUseCase()->handle(self::ASSIGNMENT_ID, $this->dto(teacherId: 2), actorUserId: null);
    }

    public function test_a_manually_decided_sin_catalogo_record_cannot_be_corrected(): void
    {
        $this->givenProposal(teacherId: 1, result: VerificationResult::NoCatalog, status: ProposalStatus::Confirmed);

        $this->expectException(InvalidAssignmentTransitionException::class);

        $this->editUseCase()->handle(self::ASSIGNMENT_ID, $this->dto(teacherId: 2), actorUserId: null);
    }

    public function test_correcting_a_record_that_does_not_exist_is_refused(): void
    {
        $this->expectException(InvalidAssignmentTransitionException::class);

        $this->editUseCase()->handle(404, $this->dto(teacherId: 2), actorUserId: null);
    }

    public function test_an_accidental_proposal_can_be_deleted(): void
    {
        $this->givenProposal(teacherId: 1, result: VerificationResult::NotMatched);

        $this->deleteUseCase()->handle(self::ASSIGNMENT_ID, actorUserId: 6);

        $this->assertNull($this->assignments->find(self::ASSIGNMENT_ID));
    }

    public function test_deleting_an_accidental_proposal_is_recorded_in_the_audit_trail(): void
    {
        $this->givenProposal(teacherId: 1, result: VerificationResult::NotMatched);

        $this->deleteUseCase()->handle(self::ASSIGNMENT_ID, actorUserId: 6);

        $entries = $this->auditLog->entries();
        $this->assertCount(1, $entries);
        $this->assertSame(AuditLogEntry::ACTION_DELETED, $entries[0]->action());
        $this->assertSame(['before' => 'not_matched', 'after' => null], $entries[0]->changes()['result']);
    }

    public function test_an_auto_confirmed_atinente_proposal_can_still_be_deleted(): void
    {
        $this->givenProposal(teacherId: 1, result: VerificationResult::Matched, status: ProposalStatus::Confirmed);

        $this->deleteUseCase()->handle(self::ASSIGNMENT_ID, actorUserId: null);

        $this->assertNull($this->assignments->find(self::ASSIGNMENT_ID));
    }

    public function test_a_record_with_a_technical_note_cannot_be_deleted(): void
    {
        $this->givenProposal(teacherId: 1, result: VerificationResult::TechnicalNote);
        $this->notes->save($this->technicalNote());

        $this->expectException(InvalidAssignmentTransitionException::class);

        $this->deleteUseCase()->handle(self::ASSIGNMENT_ID, actorUserId: null);
    }

    public function test_a_manually_decided_sin_catalogo_record_cannot_be_deleted(): void
    {
        $this->givenProposal(teacherId: 1, result: VerificationResult::NoCatalog, status: ProposalStatus::Confirmed);

        $this->expectException(InvalidAssignmentTransitionException::class);

        $this->deleteUseCase()->handle(self::ASSIGNMENT_ID, actorUserId: null);
    }

    public function test_deleting_a_record_that_does_not_exist_is_refused(): void
    {
        $this->expectException(InvalidAssignmentTransitionException::class);

        $this->deleteUseCase()->handle(404, actorUserId: null);
    }

    private function editUseCase(): EditTeacherAssignmentUseCase
    {
        return new EditTeacherAssignmentUseCase(
            $this->assignments,
            $this->verifications,
            $this->notes,
            new RunAffinityVerificationUseCase(
                $this->assignments,
                $this->verifications,
                $this->credentials,
                new ResolveApplicableCatalogVersionUseCase($this->catalog, new CatalogVersionResolver),
            ),
            $this->auditLog,
        );
    }

    private function deleteUseCase(): DeleteTeacherAssignmentUseCase
    {
        return new DeleteTeacherAssignmentUseCase($this->assignments, $this->verifications, $this->notes, $this->auditLog);
    }

    private function givenProposal(int $teacherId, VerificationResult $result, ProposalStatus $status = ProposalStatus::Proposed): void
    {
        $this->assignments->save(new TeacherAssignment(
            id: self::ASSIGNMENT_ID,
            courseGroupId: 1,
            teacherId: $teacherId,
            status: $status,
        ));
        $this->verifications->save($this->verification($result));
    }

    private function verification(VerificationResult $result): AffinityVerification
    {
        return new AffinityVerification(
            id: null,
            teacherAssignmentId: self::ASSIGNMENT_ID,
            catalogVersionId: null,
            matchedCredentialId: null,
            performedByUserId: null,
            result: $result,
            isProvisional: false,
            justification: null,
            performedAt: new DateTimeImmutable('2026-05-01'),
        );
    }

    private function technicalNote(): TechnicalNote
    {
        return new TechnicalNote(
            id: null,
            teacherAssignmentId: self::ASSIGNMENT_ID,
            documentPath: 'technical-notes/criterion.pdf',
            submittedByUserId: null,
            ratificationDeadline: new DateTimeImmutable('2026-12-31'),
            status: TechnicalNoteStatus::PendingRatification,
            ratifiedAt: null,
        );
    }

    private function dto(int $teacherId): ProposeTeacherAssignmentDTO
    {
        return new ProposeTeacherAssignmentDTO(
            courseGroupId: 1,
            teacherId: $teacherId,
            courseId: self::COURSE_ID,
            targetDate: self::TARGET_DATE,
        );
    }

    private function giveCredential(int $teacherId, int $specialtyId): void
    {
        (new RegisterAcademicCredentialUseCase($this->credentials, new InMemoryAuditLogRepository))->handle(
            new AcademicCredentialDTO(
                teacherId: $teacherId,
                specialtyId: $specialtyId,
                degreeLevel: DegreeLevel::Bachelor,
                institution: 'Universidad Técnica Nacional',
                startDate: new DateTimeImmutable('2010-03-01'),
                endDate: new DateTimeImmutable('2015-11-30'),
            ),
            actorUserId: null,
        );
    }

    private function publishCatalog(): void
    {
        $this->catalog->save(new AffinityCatalogVersion(
            id: null,
            courseId: self::COURSE_ID,
            versionNumber: 1,
            councilAgreement: 'Acuerdo 1-2026',
            gazetteNumber: '10',
            effectiveStartDate: new DateTimeImmutable('2026-01-01'),
            effectiveEndDate: new DateTimeImmutable('2026-12-31'),
            specialtyIds: [self::AFFINE_SPECIALTY],
        ));
    }
}
