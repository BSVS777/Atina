<?php

namespace Tests\Unit\Academic\Fakes;

use Src\Academic\AffinityCatalog\Domain\Contracts\AffinityCatalogVersionRepositoryInterface;
use Src\Academic\AffinityCatalog\Domain\Entities\AffinityCatalogVersion;

final class InMemoryAffinityCatalogVersionRepository implements AffinityCatalogVersionRepositoryInterface
{
    /** @var array<int, AffinityCatalogVersion> */
    private array $versions = [];

    private int $nextId = 1;

    /** @var array<int, bool> */
    private array $versionsInUse = [];

    public function find(int $id): ?AffinityCatalogVersion
    {
        return $this->versions[$id] ?? null;
    }

    public function forCourse(int $courseId): array
    {
        $forCourse = array_values(array_filter(
            $this->versions,
            fn (AffinityCatalogVersion $version) => $version->courseId() === $courseId,
        ));

        usort($forCourse, fn (AffinityCatalogVersion $a, AffinityCatalogVersion $b) => $b->versionNumber() <=> $a->versionNumber());

        return $forCourse;
    }

    public function courseIdsWithCatalog(): array
    {
        return array_values(array_unique(array_map(
            fn (AffinityCatalogVersion $version) => $version->courseId(),
            $this->versions,
        )));
    }

    public function nextVersionNumber(int $courseId): int
    {
        return count($this->forCourse($courseId)) + 1;
    }

    public function save(AffinityCatalogVersion $version): AffinityCatalogVersion
    {
        $id = $version->id() ?? $this->nextId++;
        $saved = $version->id() === null ? $version->withId($id) : $version;
        $this->versions[$id] = $saved;

        return $saved;
    }

    public function hasVerifications(int $catalogVersionId): bool
    {
        return $this->versionsInUse[$catalogVersionId] ?? false;
    }

    public function markInUse(int $catalogVersionId): void
    {
        $this->versionsInUse[$catalogVersionId] = true;
    }
}
