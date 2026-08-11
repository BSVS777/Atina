<?php

declare(strict_types=1);

namespace Src\Academic\AffinityCatalog\Application\UseCases;

use DateTimeImmutable;
use Src\Academic\AffinityCatalog\Application\DTOs\AffinityCatalogVersionDTO;
use Src\Academic\AffinityCatalog\Domain\Contracts\AffinityCatalogVersionRepositoryInterface;
use Src\Academic\AffinityCatalog\Domain\Entities\AffinityCatalogVersion;
use Src\Academic\AffinityCatalog\Domain\Exceptions\OverlappingCatalogVersionException;
use Src\Shared\Audit\Domain\Contracts\AuditLogRepositoryInterface;
use Src\Shared\Audit\Domain\Entities\AuditLogEntry;

/**
 * DO-02: every call creates a brand new version — there is no "edit" of
 * an existing version. The version number auto-increments per course
 * and prior versions are never touched, satisfying "cada actualización
 * crea una nueva versión sin eliminar las anteriores."
 */
final class CreateAffinityCatalogVersionUseCase
{
    public const AUDITABLE_TYPE = 'affinity_catalog_version';

    public function __construct(
        private readonly AffinityCatalogVersionRepositoryInterface $repository,
        private readonly AuditLogRepositoryInterface $auditLog,
    ) {}

    public function handle(AffinityCatalogVersionDTO $dto, ?int $actorUserId): AffinityCatalogVersion
    {
        $start = new DateTimeImmutable($dto->effectiveStartDate);
        $end = $dto->effectiveEndDate !== null ? new DateTimeImmutable($dto->effectiveEndDate) : null;

        foreach ($this->repository->forCourse($dto->courseId) as $existing) {
            if ($existing->overlapsRange($start, $end)) {
                throw OverlappingCatalogVersionException::forCourse($dto->courseId);
            }
        }

        $version = new AffinityCatalogVersion(
            id: null,
            courseId: $dto->courseId,
            versionNumber: $this->repository->nextVersionNumber($dto->courseId),
            councilAgreement: $dto->councilAgreement,
            gazetteNumber: $dto->gazetteNumber,
            effectiveStartDate: $start,
            effectiveEndDate: $end,
            specialtyIds: $dto->specialtyIds,
        );

        $saved = $this->repository->save($version);
        $savedId = $saved->id() ?? throw new \LogicException('The repository must return the saved version with an id.');

        $this->auditLog->record(new AuditLogEntry(
            actorUserId: $actorUserId,
            auditableType: self::AUDITABLE_TYPE,
            auditableId: $savedId,
            action: AuditLogEntry::ACTION_CREATED,
            changes: [
                'course_id' => ['before' => null, 'after' => $dto->courseId],
                'version_number' => ['before' => null, 'after' => $saved->versionNumber()],
                'council_agreement' => ['before' => null, 'after' => $dto->councilAgreement],
                'gazette_number' => ['before' => null, 'after' => $dto->gazetteNumber],
                'effective_start_date' => ['before' => null, 'after' => $dto->effectiveStartDate],
                'effective_end_date' => ['before' => null, 'after' => $dto->effectiveEndDate],
            ],
        ));

        return $saved;
    }
}
