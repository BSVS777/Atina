<?php

declare(strict_types=1);

namespace Src\Academic\AffinityCatalog\Domain\Services;

use DateTimeImmutable;
use Src\Academic\AffinityCatalog\Domain\Entities\AffinityCatalogVersion;

/**
 * DO-02's version-resolution rule: select the entry whose validity
 * covers the destination quarter's start date; if none covers it,
 * apply the "closest available" entry and flag it provisional.
 *
 * "Closest available" (D5/D6 — see Docs/DIARIO_DECISIONES_IA.md): the
 * version with the most recent effective start date that is still on or
 * before the target date; if every version starts after the target
 * date, fall back to the earliest version instead. Both fallback cases
 * are flagged provisional.
 */
final class CatalogVersionResolver
{
    /**
     * @param  array<int, AffinityCatalogVersion>  $versions  All versions for one course. Empty means "no catalog" — caller's responsibility (DO-02d).
     */
    public function resolve(array $versions, DateTimeImmutable $targetDate): ?ResolvedCatalogVersion
    {
        if ($versions === []) {
            return null;
        }

        foreach ($versions as $version) {
            if ($version->coversDate($targetDate)) {
                return new ResolvedCatalogVersion($version, isProvisional: false);
            }
        }

        $priorVersions = array_filter($versions, fn (AffinityCatalogVersion $v) => $v->effectiveStartDate() <= $targetDate);

        if ($priorVersions !== []) {
            $latest = $this->latestByStartDate($priorVersions);

            return new ResolvedCatalogVersion($latest, isProvisional: true);
        }

        $earliest = $this->earliestByStartDate($versions);

        return new ResolvedCatalogVersion($earliest, isProvisional: true);
    }

    /**
     * @param  array<int, AffinityCatalogVersion>  $versions
     */
    private function latestByStartDate(array $versions): AffinityCatalogVersion
    {
        usort($versions, fn (AffinityCatalogVersion $a, AffinityCatalogVersion $b) => $b->effectiveStartDate() <=> $a->effectiveStartDate());

        return $versions[0];
    }

    /**
     * @param  array<int, AffinityCatalogVersion>  $versions
     */
    private function earliestByStartDate(array $versions): AffinityCatalogVersion
    {
        usort($versions, fn (AffinityCatalogVersion $a, AffinityCatalogVersion $b) => $a->effectiveStartDate() <=> $b->effectiveStartDate());

        return $versions[0];
    }
}
