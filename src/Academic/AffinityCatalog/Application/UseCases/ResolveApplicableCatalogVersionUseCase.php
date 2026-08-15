<?php

declare(strict_types=1);

namespace Src\Academic\AffinityCatalog\Application\UseCases;

use DateTimeImmutable;
use Src\Academic\AffinityCatalog\Domain\Contracts\AffinityCatalogVersionRepositoryInterface;
use Src\Academic\AffinityCatalog\Domain\Services\CatalogVersionResolver;
use Src\Academic\AffinityCatalog\Domain\Services\ResolvedCatalogVersion;

/**
 * DO-02: resolves which catalog version applies to a course for a given
 * target date (a course group's term start date). Returns null when the
 * course has no catalog entries at all — the caller (DO-02a) turns that
 * into the "Sin catálogo" result (DO-02d).
 */
final class ResolveApplicableCatalogVersionUseCase
{
    public function __construct(
        private readonly AffinityCatalogVersionRepositoryInterface $repository,
        private readonly CatalogVersionResolver $resolver,
    ) {}

    public function handle(int $courseId, DateTimeImmutable $targetDate): ?ResolvedCatalogVersion
    {
        return $this->resolver->resolve($this->repository->forCourse($courseId), $targetDate);
    }
}
