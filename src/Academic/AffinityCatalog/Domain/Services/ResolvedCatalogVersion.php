<?php

declare(strict_types=1);

namespace Src\Academic\AffinityCatalog\Domain\Services;

use Src\Academic\AffinityCatalog\Domain\Entities\AffinityCatalogVersion;

/**
 * The outcome of CatalogVersionResolver::resolve() — the applicable
 * version plus whether it was an exact validity match or a provisional
 * fallback (D5/D6).
 */
final class ResolvedCatalogVersion
{
    public function __construct(
        public readonly AffinityCatalogVersion $version,
        public readonly bool $isProvisional,
    ) {}
}
