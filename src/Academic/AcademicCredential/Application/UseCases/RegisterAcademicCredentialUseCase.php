<?php

declare(strict_types=1);

namespace Src\Academic\AcademicCredential\Application\UseCases;

use Src\Academic\AcademicCredential\Application\DTOs\AcademicCredentialDTO;
use Src\Academic\AcademicCredential\Domain\Contracts\AcademicCredentialRepositoryInterface;
use Src\Academic\AcademicCredential\Domain\Entities\AcademicCredential;
use Src\Academic\AcademicCredential\Domain\Exceptions\DuplicateCredentialException;
use Src\Academic\AcademicCredential\Domain\StudyPeriod;
use Src\Shared\Audit\Domain\Contracts\AuditLogRepositoryInterface;
use Src\Shared\Audit\Domain\Entities\AuditLogEntry;

/**
 * Authorization is enforced by AcademicCredentialPolicy at the Presentation
 * boundary (same convention as Role/Permission), not re-checked here.
 */
final class RegisterAcademicCredentialUseCase
{
    public const AUDITABLE_TYPE = 'academic_credential';

    public function __construct(
        private readonly AcademicCredentialRepositoryInterface $repository,
        private readonly AuditLogRepositoryInterface $auditLog,
    ) {}

    public function handle(AcademicCredentialDTO $dto, ?int $actorUserId): AcademicCredential
    {
        if ($this->repository->existsForTeacherSpecialtyDegree($dto->teacherId, $dto->specialtyId, $dto->degreeLevel)) {
            throw DuplicateCredentialException::forTeacherSpecialtyDegree();
        }

        $credential = new AcademicCredential(
            id: null,
            teacherId: $dto->teacherId,
            specialtyId: $dto->specialtyId,
            degreeLevel: $dto->degreeLevel,
            institution: $dto->institution,
            studyPeriod: new StudyPeriod($dto->startDate, $dto->endDate),
        );

        $saved = $this->repository->save($credential);
        $savedId = $saved->id() ?? throw new \LogicException('The repository must return the saved credential with an id.');

        $this->auditLog->record(new AuditLogEntry(
            actorUserId: $actorUserId,
            auditableType: self::AUDITABLE_TYPE,
            auditableId: $savedId,
            action: AuditLogEntry::ACTION_CREATED,
            changes: [
                'specialty_id' => ['before' => null, 'after' => $dto->specialtyId],
                'degree_level' => ['before' => null, 'after' => $dto->degreeLevel->value],
                'institution' => ['before' => null, 'after' => $dto->institution],
                'start_date' => ['before' => null, 'after' => $dto->startDate->format('Y-m-d')],
                'end_date' => ['before' => null, 'after' => $dto->endDate->format('Y-m-d')],
            ],
        ));

        return $saved;
    }
}
