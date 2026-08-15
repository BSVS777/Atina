<?php

declare(strict_types=1);

namespace Src\Academic\AffinityCatalog\Application\UseCases;

use Src\Academic\AffinityCatalog\Domain\Contracts\AffinityCatalogVersionRepositoryInterface;
use Src\Academic\AffinityCatalog\Domain\Entities\AffinityCatalogVersion;

final class ListAffinityCatalogVersionsForCourseUseCase
{
    public function __construct(
        private readonly AffinityCatalogVersionRepositoryInterface $repository,
    ) {}

    /**
     * @return array<int, AffinityCatalogVersion>
     */
    public function handle(int $courseId): array
    {
        return $this->repository->forCourse($courseId);
    }
}
