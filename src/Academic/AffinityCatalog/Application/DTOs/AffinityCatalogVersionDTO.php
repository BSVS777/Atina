<?php

declare(strict_types=1);

namespace Src\Academic\AffinityCatalog\Application\DTOs;

final class AffinityCatalogVersionDTO
{
    /**
     * @param  array<int, int>  $specialtyIds
     */
    public function __construct(
        public readonly int $courseId,
        public readonly string $councilAgreement,
        public readonly string $gazetteNumber,
        public readonly string $effectiveStartDate,
        public readonly ?string $effectiveEndDate,
        public readonly array $specialtyIds,
    ) {}
}
