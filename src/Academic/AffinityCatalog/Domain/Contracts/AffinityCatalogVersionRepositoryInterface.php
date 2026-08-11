<?php

declare(strict_types=1);

namespace Src\Academic\AffinityCatalog\Domain\Contracts;

use Src\Academic\AffinityCatalog\Domain\Entities\AffinityCatalogVersion;

interface AffinityCatalogVersionRepositoryInterface
{
    public function find(int $id): ?AffinityCatalogVersion;

    /**
     * @return array<int, AffinityCatalogVersion> Ordered by version_number desc.
     */
    public function forCourse(int $courseId): array;

    /**
     * @return array<int, int> Course ids that have at least one catalog version.
     */
    public function courseIdsWithCatalog(): array;

    public function nextVersionNumber(int $courseId): int;

    public function save(AffinityCatalogVersion $version): AffinityCatalogVersion;
}
