<?php

namespace Tests\Unit\Academic;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Src\Academic\AcademicCredential\Application\DTOs\AcademicCredentialDTO;
use Src\Academic\AcademicCredential\Application\UseCases\RegisterAcademicCredentialUseCase;
use Src\Academic\AcademicCredential\Domain\DegreeLevel;
use Src\Academic\AcademicCredential\Domain\Exceptions\DuplicateCredentialException;
use Src\Shared\Audit\Domain\Entities\AuditLogEntry;
use Tests\Unit\Academic\Fakes\InMemoryAcademicCredentialRepository;
use Tests\Unit\Academic\Fakes\InMemoryAuditLogRepository;

class RegisterAcademicCredentialUseCaseTest extends TestCase
{
    private InMemoryAcademicCredentialRepository $credentials;

    private InMemoryAuditLogRepository $auditLog;

    private RegisterAcademicCredentialUseCase $useCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->credentials = new InMemoryAcademicCredentialRepository;
        $this->auditLog = new InMemoryAuditLogRepository;
        $this->useCase = new RegisterAcademicCredentialUseCase($this->credentials, $this->auditLog);
    }

    public function test_registers_a_credential_and_records_a_creation_audit_entry(): void
    {
        $credential = $this->useCase->handle($this->dto(), actorUserId: 7);

        $this->assertNotNull($credential->id());
        $this->assertSame(1, $credential->teacherId());

        $entries = $this->auditLog->entries();
        $this->assertCount(1, $entries);
        $this->assertSame(AuditLogEntry::ACTION_CREATED, $entries[0]->action());
        $this->assertSame(7, $entries[0]->actorUserId());
        $this->assertSame($credential->id(), $entries[0]->auditableId());
    }

    public function test_rejects_a_duplicate_teacher_specialty_degree_combination(): void
    {
        $this->useCase->handle($this->dto(), actorUserId: 1);

        $this->expectException(DuplicateCredentialException::class);

        $this->useCase->handle($this->dto(), actorUserId: 1);
    }

    private function dto(): AcademicCredentialDTO
    {
        return new AcademicCredentialDTO(
            teacherId: 1,
            specialtyId: 1,
            degreeLevel: DegreeLevel::Bachelor,
            institution: 'Universidad Técnica Nacional',
            startDate: new DateTimeImmutable('2010-03-01'),
            endDate: new DateTimeImmutable('2015-11-30'),
        );
    }
}
