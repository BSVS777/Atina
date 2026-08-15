<?php

declare(strict_types=1);

namespace Src\Academic\AcademicCredential\Application\UseCases;

use Src\Academic\AcademicCredential\Application\DTOs\AcademicCredentialDTO;
use Src\Academic\AcademicCredential\Domain\Contracts\AcademicCredentialRepositoryInterface;
use Src\Academic\AcademicCredential\Domain\Entities\AcademicCredential;
use Src\Academic\AcademicCredential\Domain\Exceptions\CredentialNotFoundException;
use Src\Academic\AcademicCredential\Domain\Exceptions\DuplicateCredentialException;
use Src\Academic\AcademicCredential\Domain\YearObtained;
use Src\Shared\Audit\Domain\Contracts\AuditLogRepositoryInterface;
use Src\Shared\Audit\Domain\Entities\AuditLogEntry;

/**
 * Only an effective modification (at least one changed field) is audited,
 * matching the source business rule this was ported from.
 */
final class EditAcademicCredentialUseCase
{
    public function __construct(
        private readonly AcademicCredentialRepositoryInterface $repository,
        private readonly AuditLogRepositoryInterface $auditLog,
    ) {}

    public function handle(int $credentialId, AcademicCredentialDTO $dto, ?int $actorUserId): AcademicCredential
    {
        $existing = $this->repository->find($credentialId)
            ?? throw CredentialNotFoundException::withId($credentialId);

        if ($this->repository->existsForTeacherSpecialtyDegree(
            $existing->teacherId(),
            $dto->specialtyId,
            $dto->degreeLevel,
            exceptCredentialId: $credentialId,
        )) {
            throw DuplicateCredentialException::forTeacherSpecialtyDegree();
        }

        $changes = $this->diff($existing, $dto);

        $updated = new AcademicCredential(
            id: $credentialId,
            teacherId: $existing->teacherId(),
            specialtyId: $dto->specialtyId,
            degreeLevel: $dto->degreeLevel,
            institution: $dto->institution,
            yearObtained: new YearObtained($dto->yearObtained),
        );
        $saved = $this->repository->save($updated);

        if ($changes !== []) {
            $this->auditLog->record(new AuditLogEntry(
                actorUserId: $actorUserId,
                auditableType: RegisterAcademicCredentialUseCase::AUDITABLE_TYPE,
                auditableId: $credentialId,
                action: AuditLogEntry::ACTION_UPDATED,
                changes: $changes,
            ));
        }

        return $saved;
    }

    /**
     * @return array<string, array{before: mixed, after: mixed}>
     */
    private function diff(AcademicCredential $existing, AcademicCredentialDTO $dto): array
    {
        $changes = [];

        if ($existing->specialtyId() !== $dto->specialtyId) {
            $changes['specialty_id'] = ['before' => $existing->specialtyId(), 'after' => $dto->specialtyId];
        }

        if ($existing->degreeLevel() !== $dto->degreeLevel) {
            $changes['degree_level'] = ['before' => $existing->degreeLevel()->value, 'after' => $dto->degreeLevel->value];
        }

        if ($existing->institution() !== $dto->institution) {
            $changes['institution'] = ['before' => $existing->institution(), 'after' => $dto->institution];
        }

        if ($existing->yearObtained()->value() !== $dto->yearObtained) {
            $changes['year_obtained'] = ['before' => $existing->yearObtained()->value(), 'after' => $dto->yearObtained];
        }

        return $changes;
    }
}
