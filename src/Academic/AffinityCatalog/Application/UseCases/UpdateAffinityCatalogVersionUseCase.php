<?php

declare(strict_types=1);

namespace Src\Academic\AffinityCatalog\Application\UseCases;

use DateTimeImmutable;
use Src\Academic\AffinityCatalog\Application\DTOs\AffinityCatalogVersionDTO;
use Src\Academic\AffinityCatalog\Domain\Contracts\AffinityCatalogVersionRepositoryInterface;
use Src\Academic\AffinityCatalog\Domain\Entities\AffinityCatalogVersion;
use Src\Academic\AffinityCatalog\Domain\Exceptions\CatalogVersionInUseException;
use Src\Academic\AffinityCatalog\Domain\Exceptions\CatalogVersionNotFoundException;
use Src\Academic\AffinityCatalog\Domain\Exceptions\OverlappingCatalogVersionException;
use Src\Shared\Audit\Domain\Contracts\AuditLogRepositoryInterface;
use Src\Shared\Audit\Domain\Entities\AuditLogEntry;

/**
 * DO-02 explicitly rules out editing a published version once it has
 * been applied ("cada actualización crea una nueva versión sin eliminar
 * las anteriores" — see CreateAffinityCatalogVersionUseCase). But a
 * version that has never been cited by a verification yet has no
 * history to protect, so correcting a genuine mistake (a typo in the
 * agreement/gazette number, a wrong date) before it's ever used is safe.
 * The moment CatalogVersionRepositoryInterface::hasVerifications()
 * turns true, this use case refuses — from then on the only path is a
 * brand new version, same as CreateAffinityCatalogVersionUseCase.
 *
 * course_id and version_number are intentionally never taken from the
 * DTO here — they're read back from the existing entity, so a tampered
 * form submission can't move a version to a different course or
 * renumber it.
 */
final class UpdateAffinityCatalogVersionUseCase
{
    public function __construct(
        private readonly AffinityCatalogVersionRepositoryInterface $repository,
        private readonly AuditLogRepositoryInterface $auditLog,
    ) {}

    public function handle(int $id, AffinityCatalogVersionDTO $dto, ?int $actorUserId): AffinityCatalogVersion
    {
        $existing = $this->repository->find($id) ?? throw CatalogVersionNotFoundException::withId($id);

        if ($this->repository->hasVerifications($id)) {
            throw CatalogVersionInUseException::withId($id);
        }

        $start = new DateTimeImmutable($dto->effectiveStartDate);
        $end = $dto->effectiveEndDate !== null ? new DateTimeImmutable($dto->effectiveEndDate) : null;

        foreach ($this->repository->forCourse($existing->courseId()) as $other) {
            if ($other->id() !== $id && $other->overlapsRange($start, $end)) {
                throw OverlappingCatalogVersionException::forCourse($existing->courseId());
            }
        }

        $updated = new AffinityCatalogVersion(
            id: $id,
            courseId: $existing->courseId(),
            versionNumber: $existing->versionNumber(),
            councilAgreement: $dto->councilAgreement,
            gazetteNumber: $dto->gazetteNumber,
            effectiveStartDate: $start,
            effectiveEndDate: $end,
            specialtyIds: $dto->specialtyIds,
        );

        $saved = $this->repository->save($updated);

        $this->auditLog->record(new AuditLogEntry(
            actorUserId: $actorUserId,
            auditableType: CreateAffinityCatalogVersionUseCase::AUDITABLE_TYPE,
            auditableId: $id,
            action: AuditLogEntry::ACTION_UPDATED,
            changes: [
                'council_agreement' => ['before' => $existing->councilAgreement(), 'after' => $dto->councilAgreement],
                'gazette_number' => ['before' => $existing->gazetteNumber(), 'after' => $dto->gazetteNumber],
                'effective_start_date' => ['before' => $existing->effectiveStartDate()->format('Y-m-d'), 'after' => $dto->effectiveStartDate],
                'effective_end_date' => ['before' => $existing->effectiveEndDate()?->format('Y-m-d'), 'after' => $dto->effectiveEndDate],
            ],
        ));

        return $saved;
    }
}
