<?php

namespace Tests\Unit\Academic;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Src\Academic\AffinityCatalog\Domain\Entities\AffinityCatalogVersion;
use Src\Academic\AffinityCatalog\Domain\Services\CatalogVersionResolver;

/**
 * DO-02's version-resolution rule (D5/D6, Docs/DIARIO_DECISIONES_IA.md).
 * This is the case the Functionality rubric explicitly uses to
 * distinguish "Excelente" from "Regular" — every branch is covered.
 *
 * D5 (no exact/current coverage, but a prior version exists — see
 * `test_d5_*` below) is **professor-confirmed**: apply the most recent
 * prior version and mark it provisional.
 *
 * D6 (the target date predates every existing version — see
 * `test_d6_target_date_before_all_versions_applies_the_earliest_as_provisional`)
 * is a **separate, still-unconfirmed** edge case: whether a future
 * version may apply retroactively. The professor has not answered this;
 * the fallback-to-earliest-version behavior below is preserved
 * unchanged and must not be read as professor-confirmed.
 */
class CatalogVersionResolverTest extends TestCase
{
    private function version(int $id, string $start, ?string $end): AffinityCatalogVersion
    {
        return new AffinityCatalogVersion(
            id: $id,
            courseId: 1,
            versionNumber: $id,
            councilAgreement: "Acuerdo {$id}",
            gazetteNumber: (string) $id,
            effectiveStartDate: new DateTimeImmutable($start),
            effectiveEndDate: $end !== null ? new DateTimeImmutable($end) : null,
            specialtyIds: [1],
        );
    }

    public function test_no_versions_returns_null(): void
    {
        $result = (new CatalogVersionResolver)->resolve([], new DateTimeImmutable('2026-05-01'));

        $this->assertNull($result);
    }

    public function test_exact_coverage_is_not_provisional(): void
    {
        $version = $this->version(1, '2026-01-01', '2026-12-31');

        $result = (new CatalogVersionResolver)->resolve([$version], new DateTimeImmutable('2026-05-01'));

        $this->assertSame($version, $result->version);
        $this->assertFalse($result->isProvisional);
    }

    public function test_open_ended_version_covers_any_future_date(): void
    {
        $version = $this->version(1, '2026-01-01', null);

        $result = (new CatalogVersionResolver)->resolve([$version], new DateTimeImmutable('2030-01-01'));

        $this->assertFalse($result->isProvisional);
    }

    public function test_d5_no_exact_match_applies_the_most_recent_prior_version_as_provisional(): void
    {
        $old = $this->version(1, '2020-01-01', '2020-12-31');
        $mostRecentPrior = $this->version(2, '2024-01-01', '2024-12-31');

        $result = (new CatalogVersionResolver)->resolve([$old, $mostRecentPrior], new DateTimeImmutable('2026-05-01'));

        $this->assertSame($mostRecentPrior, $result->version);
        $this->assertTrue($result->isProvisional);
    }

    public function test_d5_picks_by_start_date_not_by_version_number(): void
    {
        // Deliberately out of numeric/insertion order: version 3 starts
        // earlier than version 2. The resolver must pick by date, not id.
        $earlierStart = $this->version(3, '2023-01-01', '2023-06-30');
        $laterStart = $this->version(2, '2024-01-01', '2024-06-30');

        $result = (new CatalogVersionResolver)->resolve([$earlierStart, $laterStart], new DateTimeImmutable('2025-01-01'));

        $this->assertSame($laterStart, $result->version);
        $this->assertTrue($result->isProvisional);
    }

    public function test_d6_target_date_before_all_versions_applies_the_earliest_as_provisional(): void
    {
        $earliest = $this->version(1, '2025-01-01', '2025-12-31');
        $later = $this->version(2, '2026-01-01', '2026-12-31');

        $result = (new CatalogVersionResolver)->resolve([$later, $earliest], new DateTimeImmutable('2020-01-01'));

        $this->assertSame($earliest, $result->version);
        $this->assertTrue($result->isProvisional);
    }

    public function test_gap_between_versions_is_treated_as_no_exact_match(): void
    {
        $before = $this->version(1, '2024-01-01', '2024-06-30');
        $after = $this->version(2, '2025-01-01', '2025-06-30');

        // 2024-09-01 falls in the gap between the two ranges.
        $result = (new CatalogVersionResolver)->resolve([$before, $after], new DateTimeImmutable('2024-09-01'));

        $this->assertSame($before, $result->version);
        $this->assertTrue($result->isProvisional);
    }
}
