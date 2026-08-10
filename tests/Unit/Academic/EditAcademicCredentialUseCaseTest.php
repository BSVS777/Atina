<?php

namespace Tests\Unit\Academic;

use Src\Academic\AcademicCredential\Application\DTOs\AcademicCredentialDTO;
use Src\Academic\AcademicCredential\Application\UseCases\EditAcademicCredentialUseCase;
use Src\Academic\AcademicCredential\Application\UseCases\RegisterAcademicCredentialUseCase;
use Src\Academic\AcademicCredential\Domain\DegreeLevel;
use Src\Academic\AcademicCredential\Domain\Exceptions\CredentialNotFoundException;
use Src\Academic\AcademicCredential\Domain\Exceptions\DuplicateCredentialException;
use Src\Shared\Audit\Domain\Entities\AuditLogEntry;
use Tests\TestCase;
use Tests\Unit\Academic\Fakes\InMemoryAcademicCredentialRepository;
use Tests\Unit\Academic\Fakes\InMemoryAuditLogRepository;

class EditAcademicCredentialUseCaseTest extends TestCase
{
    private InMemoryAcademicCredentialRepository $credentials;

    private InMemoryAuditLogRepository $auditLog;

    private RegisterAcademicCredentialUseCase $registerUseCase;

    private EditAcademicCredentialUseCase $editUseCase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->credentials = new InMemoryAcademicCredentialRepository;
        $this->auditLog = new InMemoryAuditLogRepository;
        $this->registerUseCase = new RegisterAcademicCredentialUseCase($this->credentials, $this->auditLog);
        $this->editUseCase = new EditAcademicCredentialUseCase($this->credentials, $this->auditLog);
    }

    public function test_editing_with_no_actual_changes_does_not_record_an_audit_entry(): void
    {
        $created = $this->registerUseCase->handle($this->dto(), actorUserId: 1);

        $this->editUseCase->handle($created->id(), $this->dto(), actorUserId: 1);

        $entries = $this->auditLog->entries();
        $this->assertCount(1, $entries, 'Only the creation entry should exist — nothing changed on edit.');
    }

    public function test_editing_a_changed_field_records_only_that_field(): void
    {
        $created = $this->registerUseCase->handle($this->dto(), actorUserId: 1);

        $changed = new AcademicCredentialDTO(
            teacherId: $created->teacherId(),
            specialtyId: $created->specialtyId(),
            degreeLevel: $created->degreeLevel(),
            institution: 'University of Costa Rica',
            yearObtained: $created->yearObtained()->value(),
        );

        $this->editUseCase->handle($created->id(), $changed, actorUserId: 2);

        $entries = $this->auditLog->entries();
        $this->assertCount(2, $entries);

        $editEntry = $entries[1];
        $this->assertSame(AuditLogEntry::ACTION_UPDATED, $editEntry->action());
        $this->assertSame(['institution'], array_keys($editEntry->changes()));
        $this->assertSame('National Technical University', $editEntry->changes()['institution']['before']);
        $this->assertSame('University of Costa Rica', $editEntry->changes()['institution']['after']);
    }

    public function test_rejects_editing_into_a_duplicate_combination(): void
    {
        $first = $this->registerUseCase->handle($this->dto(), actorUserId: 1);
        $second = $this->registerUseCase->handle(new AcademicCredentialDTO(
            teacherId: $first->teacherId(),
            specialtyId: 2,
            degreeLevel: DegreeLevel::Master,
            institution: 'University of Costa Rica',
            yearObtained: 2018,
        ), actorUserId: 1);

        $this->expectException(DuplicateCredentialException::class);

        $this->editUseCase->handle($second->id(), $this->dto(), actorUserId: 1);
    }

    public function test_throws_when_editing_a_credential_that_does_not_exist(): void
    {
        $this->expectException(CredentialNotFoundException::class);

        $this->editUseCase->handle(999, $this->dto(), actorUserId: 1);
    }

    private function dto(): AcademicCredentialDTO
    {
        return new AcademicCredentialDTO(
            teacherId: 1,
            specialtyId: 1,
            degreeLevel: DegreeLevel::Bachelor,
            institution: 'National Technical University',
            yearObtained: 2015,
        );
    }
}
