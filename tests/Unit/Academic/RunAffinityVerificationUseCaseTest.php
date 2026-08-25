<?php

namespace Tests\Unit\Academic;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Src\Academic\AcademicCredential\Application\DTOs\AcademicCredentialDTO;
use Src\Academic\AcademicCredential\Application\UseCases\RegisterAcademicCredentialUseCase;
use Src\Academic\AcademicCredential\Domain\DegreeLevel;
use Src\Academic\AcademicCredential\Domain\Entities\AcademicCredential;
use Src\Academic\AffinityCatalog\Application\UseCases\ResolveApplicableCatalogVersionUseCase;
use Src\Academic\AffinityCatalog\Domain\Entities\AffinityCatalogVersion;
use Src\Academic\AffinityCatalog\Domain\Services\CatalogVersionResolver;
use Src\Academic\TeacherAssignment\Application\UseCases\RunAffinityVerificationUseCase;
use Src\Academic\TeacherAssignment\Domain\Entities\TeacherAssignment;
use Src\Academic\TeacherAssignment\Domain\ProposalStatus;
use Src\Academic\TeacherAssignment\Domain\VerificationResult;
use Tests\Unit\Academic\Fakes\InMemoryAcademicCredentialRepository;
use Tests\Unit\Academic\Fakes\InMemoryAffinityCatalogVersionRepository;
use Tests\Unit\Academic\Fakes\InMemoryAffinityVerificationRepository;
use Tests\Unit\Academic\Fakes\InMemoryAuditLogRepository;
use Tests\Unit\Academic\Fakes\InMemoryTeacherAssignmentRepository;

/**
 * DO-02a's automatic affinity decision, exercised directly against
 * in-memory adapters — no database, no Livewire, no HTTP. The rule under
 * test is "the teacher is Atinente iff at least one of their credentials
 * cites a specialty listed in the course's applicable catalog version";
 * degree level never participates.
 */
class RunAffinityVerificationUseCaseTest extends TestCase
{
    private const COURSE_ID = 1;

    private const TEACHER_ID = 1;

    private const TARGET_DATE = '2026-05-01';

    private InMemoryTeacherAssignmentRepository $assignments;

    private InMemoryAffinityVerificationRepository $verifications;

    private InMemoryAcademicCredentialRepository $credentials;

    private InMemoryAffinityCatalogVersionRepository $catalog;

    private RunAffinityVerificationUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assignments = new InMemoryTeacherAssignmentRepository;
        $this->verifications = new InMemoryAffinityVerificationRepository;
        $this->credentials = new InMemoryAcademicCredentialRepository;
        $this->catalog = new InMemoryAffinityCatalogVersionRepository;

        $this->useCase = new RunAffinityVerificationUseCase(
            $this->assignments,
            $this->verifications,
            $this->credentials,
            new ResolveApplicableCatalogVersionUseCase($this->catalog, new CatalogVersionResolver),
        );
    }

    public function test_a_credential_in_the_applicable_catalog_produces_atinente_and_confirms_the_assignment(): void
    {
        $programming = 10;
        $this->publishCatalog([$programming], '2026-01-01', '2026-12-31');
        $credential = $this->giveCredential($programming, DegreeLevel::Bachelor);

        $result = $this->useCase->handle($this->proposal(), self::COURSE_ID, self::TARGET_DATE, actorUserId: 7);

        $this->assertSame(VerificationResult::Matched, $result->verification->result());
        $this->assertSame(ProposalStatus::Confirmed, $result->assignment->status());
        $this->assertSame($credential->id(), $result->verification->matchedCredentialId());
        $this->assertFalse($result->verification->isProvisional());
        $this->assertSame(7, $result->verification->performedByUserId());
    }

    public function test_the_search_continues_past_a_non_matching_credential_until_one_matches(): void
    {
        $programming = 10;
        $accounting = 20;
        $this->publishCatalog([$programming], '2026-01-01', '2026-12-31');
        $this->giveCredential($accounting, DegreeLevel::Bachelor);
        $matching = $this->giveCredential($programming, DegreeLevel::Master);

        $result = $this->useCase->handle($this->proposal(), self::COURSE_ID, self::TARGET_DATE, actorUserId: null);

        $this->assertSame(VerificationResult::Matched, $result->verification->result());
        $this->assertSame($matching->id(), $result->verification->matchedCredentialId());
    }

    public function test_no_matching_credential_produces_no_atinente_and_leaves_the_assignment_undecided(): void
    {
        $programming = 10;
        $cybersecurity = 20;
        $this->publishCatalog([$programming], '2026-01-01', '2026-12-31');
        $this->giveCredential($cybersecurity, DegreeLevel::Doctorate);

        $result = $this->useCase->handle($this->proposal(), self::COURSE_ID, self::TARGET_DATE, actorUserId: null);

        $this->assertSame(VerificationResult::NotMatched, $result->verification->result());
        $this->assertSame(ProposalStatus::Proposed, $result->assignment->status());
        $this->assertNull($result->verification->matchedCredentialId());
    }

    public function test_a_teacher_without_any_credential_produces_no_atinente(): void
    {
        $this->publishCatalog([10], '2026-01-01', '2026-12-31');

        $result = $this->useCase->handle($this->proposal(), self::COURSE_ID, self::TARGET_DATE, actorUserId: null);

        $this->assertSame(VerificationResult::NotMatched, $result->verification->result());
    }

    public function test_another_teachers_matching_credential_is_never_used(): void
    {
        $programming = 10;
        $this->publishCatalog([$programming], '2026-01-01', '2026-12-31');
        $this->giveCredential($programming, DegreeLevel::Bachelor, teacherId: 99);

        $result = $this->useCase->handle($this->proposal(), self::COURSE_ID, self::TARGET_DATE, actorUserId: null);

        $this->assertSame(VerificationResult::NotMatched, $result->verification->result());
        $this->assertNull($result->verification->matchedCredentialId());
    }

    public function test_a_course_without_any_catalog_version_produces_sin_catalogo(): void
    {
        $this->giveCredential(10, DegreeLevel::Bachelor);

        $result = $this->useCase->handle($this->proposal(), self::COURSE_ID, self::TARGET_DATE, actorUserId: null);

        $this->assertSame(VerificationResult::NoCatalog, $result->verification->result());
        $this->assertSame(ProposalStatus::Proposed, $result->assignment->status());
        $this->assertNull($result->verification->catalogVersionId());
        $this->assertNull($result->verification->matchedCredentialId());
        $this->assertFalse($result->verification->isProvisional());
    }

    public function test_a_catalog_version_that_does_not_cover_the_target_date_marks_the_verification_provisional(): void
    {
        $programming = 10;
        $this->publishCatalog([$programming], '2020-01-01', '2020-12-31');
        $this->giveCredential($programming, DegreeLevel::Bachelor);

        $result = $this->useCase->handle($this->proposal(), self::COURSE_ID, self::TARGET_DATE, actorUserId: null);

        $this->assertSame(VerificationResult::Matched, $result->verification->result());
        $this->assertTrue($result->verification->isProvisional());
    }

    public function test_the_verification_cites_the_catalog_version_it_was_decided_against(): void
    {
        $programming = 10;
        $version = $this->publishCatalog([$programming], '2026-01-01', '2026-12-31');
        $this->giveCredential($programming, DegreeLevel::Bachelor);

        $result = $this->useCase->handle($this->proposal(), self::COURSE_ID, self::TARGET_DATE, actorUserId: null);

        $this->assertSame($version->id(), $result->verification->catalogVersionId());
        $this->assertFalse($result->verification->isProvisional());
    }

    public function test_degree_level_does_not_change_a_matching_outcome(): void
    {
        $programming = 10;

        $withDiploma = $this->resultForIsolatedScenario([$programming], $programming, DegreeLevel::Diploma);
        $withDoctorate = $this->resultForIsolatedScenario([$programming], $programming, DegreeLevel::Doctorate);

        $this->assertSame(VerificationResult::Matched, $withDiploma);
        $this->assertSame($withDiploma, $withDoctorate);
    }

    public function test_degree_level_does_not_rescue_a_specialty_missing_from_the_catalog(): void
    {
        $programming = 10;
        $cybersecurity = 20;

        $withDiploma = $this->resultForIsolatedScenario([$programming], $cybersecurity, DegreeLevel::Diploma);
        $withDoctorate = $this->resultForIsolatedScenario([$programming], $cybersecurity, DegreeLevel::Doctorate);

        $this->assertSame(VerificationResult::NotMatched, $withDiploma);
        $this->assertSame($withDiploma, $withDoctorate);
    }

    public function test_the_automatic_run_never_produces_the_technical_note_result(): void
    {
        // "Nota técnica" is an explicit escalation (DO-02b), never an
        // automatic outcome — this run may only produce three of the four.
        $this->publishCatalog([10], '2026-01-01', '2026-12-31');

        $result = $this->useCase->handle($this->proposal(), self::COURSE_ID, self::TARGET_DATE, actorUserId: null);

        $this->assertContains($result->verification->result(), [
            VerificationResult::Matched,
            VerificationResult::NotMatched,
            VerificationResult::NoCatalog,
        ]);
    }

    public function test_each_run_appends_a_verification_instead_of_overwriting_the_previous_one(): void
    {
        $programming = 10;
        $assignment = $this->proposal();

        $this->useCase->handle($assignment, self::COURSE_ID, self::TARGET_DATE, actorUserId: null);
        $this->publishCatalog([$programming], '2026-01-01', '2026-12-31');
        $this->giveCredential($programming, DegreeLevel::Bachelor);
        $this->useCase->handle($assignment, self::COURSE_ID, self::TARGET_DATE, actorUserId: null);

        $trail = $this->verifications->forAssignment(1);

        $this->assertCount(2, $trail);
        $this->assertSame(VerificationResult::NoCatalog, $trail[0]->result());
        $this->assertSame(VerificationResult::Matched, $trail[1]->result());
    }

    public function test_running_a_verification_on_an_unpersisted_assignment_is_a_programming_error(): void
    {
        $this->expectException(\LogicException::class);

        $this->useCase->handle(
            new TeacherAssignment(id: null, courseGroupId: 1, teacherId: self::TEACHER_ID, status: ProposalStatus::Proposed),
            self::COURSE_ID,
            self::TARGET_DATE,
            actorUserId: null,
        );
    }

    /**
     * Two scenarios identical except for the credential's degree level,
     * each run against its own untouched set of in-memory adapters.
     *
     * @param  array<int, int>  $catalogSpecialtyIds
     */
    private function resultForIsolatedScenario(array $catalogSpecialtyIds, int $credentialSpecialtyId, DegreeLevel $degreeLevel): VerificationResult
    {
        $assignments = new InMemoryTeacherAssignmentRepository;
        $credentials = new InMemoryAcademicCredentialRepository;
        $catalog = new InMemoryAffinityCatalogVersionRepository;

        $catalog->save($this->catalogVersion($catalogSpecialtyIds, '2026-01-01', '2026-12-31'));
        (new RegisterAcademicCredentialUseCase($credentials, new InMemoryAuditLogRepository))
            ->handle($this->credentialDto($credentialSpecialtyId, $degreeLevel, self::TEACHER_ID), actorUserId: null);

        $assignment = $assignments->save(new TeacherAssignment(
            id: null,
            courseGroupId: 1,
            teacherId: self::TEACHER_ID,
            status: ProposalStatus::Proposed,
        ));

        $useCase = new RunAffinityVerificationUseCase(
            $assignments,
            new InMemoryAffinityVerificationRepository,
            $credentials,
            new ResolveApplicableCatalogVersionUseCase($catalog, new CatalogVersionResolver),
        );

        return $useCase->handle($assignment, self::COURSE_ID, self::TARGET_DATE, actorUserId: null)->verification->result();
    }

    private function proposal(): TeacherAssignment
    {
        return $this->assignments->save(new TeacherAssignment(
            id: null,
            courseGroupId: 1,
            teacherId: self::TEACHER_ID,
            status: ProposalStatus::Proposed,
        ));
    }

    /**
     * @param  array<int, int>  $specialtyIds
     */
    private function publishCatalog(array $specialtyIds, string $start, ?string $end): AffinityCatalogVersion
    {
        return $this->catalog->save($this->catalogVersion($specialtyIds, $start, $end));
    }

    /**
     * @param  array<int, int>  $specialtyIds
     */
    private function catalogVersion(array $specialtyIds, string $start, ?string $end): AffinityCatalogVersion
    {
        return new AffinityCatalogVersion(
            id: null,
            courseId: self::COURSE_ID,
            versionNumber: 1,
            councilAgreement: 'Acuerdo 1-2026',
            gazetteNumber: '10',
            effectiveStartDate: new DateTimeImmutable($start),
            effectiveEndDate: $end !== null ? new DateTimeImmutable($end) : null,
            specialtyIds: $specialtyIds,
        );
    }

    private function giveCredential(int $specialtyId, DegreeLevel $degreeLevel, int $teacherId = self::TEACHER_ID): AcademicCredential
    {
        return (new RegisterAcademicCredentialUseCase($this->credentials, new InMemoryAuditLogRepository))
            ->handle($this->credentialDto($specialtyId, $degreeLevel, $teacherId), actorUserId: null);
    }

    private function credentialDto(int $specialtyId, DegreeLevel $degreeLevel, int $teacherId): AcademicCredentialDTO
    {
        return new AcademicCredentialDTO(
            teacherId: $teacherId,
            specialtyId: $specialtyId,
            degreeLevel: $degreeLevel,
            institution: 'Universidad Técnica Nacional',
            startDate: new DateTimeImmutable('2010-03-01'),
            endDate: new DateTimeImmutable('2015-11-30'),
        );
    }
}
