<?php

namespace Tests\Unit\Academic;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Src\Academic\TeacherAssignment\Application\UseCases\DecideNoCatalogAssignmentUseCase;
use Src\Academic\TeacherAssignment\Domain\Entities\AffinityVerification;
use Src\Academic\TeacherAssignment\Domain\Entities\TeacherAssignment;
use Src\Academic\TeacherAssignment\Domain\Exceptions\InvalidAssignmentTransitionException;
use Src\Academic\TeacherAssignment\Domain\ProposalStatus;
use Src\Academic\TeacherAssignment\Domain\VerificationResult;
use Src\Shared\Audit\Domain\Entities\AuditLogEntry;
use Tests\Unit\Academic\Fakes\InMemoryAffinityVerificationRepository;
use Tests\Unit\Academic\Fakes\InMemoryAuditLogRepository;
use Tests\Unit\Academic\Fakes\InMemoryTeacherAssignmentRepository;

/**
 * DO-02d: a "Sin catálogo" proposal stays pending until the Coordinadora
 * de Docencia approves or rejects it manually — and that decision can
 * only be taken once, and only for that result.
 */
class DecideNoCatalogAssignmentUseCaseTest extends TestCase
{
    private const ASSIGNMENT_ID = 1;

    private InMemoryTeacherAssignmentRepository $assignments;

    private InMemoryAffinityVerificationRepository $verifications;

    private InMemoryAuditLogRepository $auditLog;

    private DecideNoCatalogAssignmentUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assignments = new InMemoryTeacherAssignmentRepository;
        $this->verifications = new InMemoryAffinityVerificationRepository;
        $this->auditLog = new InMemoryAuditLogRepository;
        $this->useCase = new DecideNoCatalogAssignmentUseCase($this->assignments, $this->verifications, $this->auditLog);
    }

    public function test_approving_a_pending_no_catalog_proposal_confirms_it(): void
    {
        $this->givenNoCatalogProposal(ProposalStatus::Proposed);

        $decided = $this->useCase->handle(self::ASSIGNMENT_ID, approve: true, actorUserId: 5);

        $this->assertSame(ProposalStatus::Confirmed, $decided->status());
    }

    public function test_rejecting_a_pending_no_catalog_proposal_rejects_it(): void
    {
        $this->givenNoCatalogProposal(ProposalStatus::Proposed);

        $decided = $this->useCase->handle(self::ASSIGNMENT_ID, approve: false, actorUserId: 5);

        $this->assertSame(ProposalStatus::Rejected, $decided->status());
    }

    public function test_the_manual_decision_is_recorded_in_the_audit_trail(): void
    {
        $this->givenNoCatalogProposal(ProposalStatus::Proposed);

        $this->useCase->handle(self::ASSIGNMENT_ID, approve: true, actorUserId: 5);

        $entries = $this->auditLog->entries();
        $this->assertCount(1, $entries);
        $this->assertSame(AuditLogEntry::ACTION_UPDATED, $entries[0]->action());
        $this->assertSame(5, $entries[0]->actorUserId());
        $this->assertSame(
            ['before' => 'proposed', 'after' => 'confirmed'],
            $entries[0]->changes()['status'],
        );
    }

    #[DataProvider('decidedStatuses')]
    public function test_an_assignment_that_was_already_decided_cannot_be_decided_again(ProposalStatus $status): void
    {
        $this->givenNoCatalogProposal($status);

        $this->expectException(InvalidAssignmentTransitionException::class);

        $this->useCase->handle(self::ASSIGNMENT_ID, approve: true, actorUserId: null);
    }

    /**
     * @return array<string, array{ProposalStatus}>
     */
    public static function decidedStatuses(): array
    {
        return [
            'already approved' => [ProposalStatus::Confirmed],
            'already rejected' => [ProposalStatus::Rejected],
        ];
    }

    #[DataProvider('ineligibleResults')]
    public function test_only_a_sin_catalogo_result_can_be_decided_manually(VerificationResult $result): void
    {
        $this->persistAssignment(ProposalStatus::Proposed);
        $this->recordVerification($result);

        $this->expectException(InvalidAssignmentTransitionException::class);

        $this->useCase->handle(self::ASSIGNMENT_ID, approve: true, actorUserId: null);
    }

    /**
     * @return array<string, array{VerificationResult}>
     */
    public static function ineligibleResults(): array
    {
        return [
            'atinente' => [VerificationResult::Matched],
            'no atinente' => [VerificationResult::NotMatched],
            'nota tecnica' => [VerificationResult::TechnicalNote],
        ];
    }

    public function test_an_assignment_without_any_verification_cannot_be_decided_manually(): void
    {
        $this->persistAssignment(ProposalStatus::Proposed);

        $this->expectException(InvalidAssignmentTransitionException::class);

        $this->useCase->handle(self::ASSIGNMENT_ID, approve: true, actorUserId: null);
    }

    public function test_the_latest_verification_is_what_decides_eligibility(): void
    {
        // An older "Sin catálogo" event must not unlock a proposal whose
        // current state is "No Atinente".
        $this->persistAssignment(ProposalStatus::Proposed);
        $this->recordVerification(VerificationResult::NoCatalog);
        $this->recordVerification(VerificationResult::NotMatched);

        $this->expectException(InvalidAssignmentTransitionException::class);

        $this->useCase->handle(self::ASSIGNMENT_ID, approve: true, actorUserId: null);
    }

    private function givenNoCatalogProposal(ProposalStatus $status): void
    {
        $this->persistAssignment($status);
        $this->recordVerification(VerificationResult::NoCatalog);
    }

    private function persistAssignment(ProposalStatus $status): TeacherAssignment
    {
        return $this->assignments->save(new TeacherAssignment(
            id: self::ASSIGNMENT_ID,
            courseGroupId: 1,
            teacherId: 1,
            status: $status,
        ));
    }

    private function recordVerification(VerificationResult $result): void
    {
        $this->verifications->save(new AffinityVerification(
            id: null,
            teacherAssignmentId: self::ASSIGNMENT_ID,
            catalogVersionId: null,
            matchedCredentialId: null,
            performedByUserId: null,
            result: $result,
            isProvisional: false,
            justification: null,
            performedAt: new DateTimeImmutable('2026-05-01'),
        ));
    }
}
