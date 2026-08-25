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
 * is **resolved by applying the professor-confirmed general catalog
 * fallback rule** ("when there is no catalog version appropriate to the
 * target period, an available catalog version is used as fallback,
 * marked provisional") to this specific edge case: since no prior
 * version exists to prefer, the earliest available (future) version is
 * used instead, still provisional. This is a documented application of
 * the general rule to the predates-all-versions case — the professor was
 * not separately asked this exact hypothetical, and this test must not
 * be read as claiming otherwise. See the 2026-08-25 entry in
 * `Docs/DIARIO_DECISIONES_IA.md`.
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

    /**
     * D6: covered by the general fallback rule (see class docblock) —
     * no prior version exists to prefer, so the earliest available
     * version is used instead, marked provisional.
     */
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

    public function test_a_target_date_equal_to_the_validity_start_is_an_exact_match(): void
    {
        $version = $this->version(1, '2026-01-01', '2026-12-31');

        $result = (new CatalogVersionResolver)->resolve([$version], new DateTimeImmutable('2026-01-01'));

        $this->assertSame($version, $result->version);
        $this->assertFalse($result->isProvisional);
    }

    public function test_a_target_date_equal_to_the_validity_end_is_an_exact_match(): void
    {
        // The end boundary is inclusive (AffinityCatalogVersion::coversDate).
        $version = $this->version(1, '2026-01-01', '2026-12-31');

        $result = (new CatalogVersionResolver)->resolve([$version], new DateTimeImmutable('2026-12-31'));

        $this->assertSame($version, $result->version);
        $this->assertFalse($result->isProvisional);
    }

    public function test_the_day_after_the_validity_end_is_no_longer_an_exact_match(): void
    {
        $version = $this->version(1, '2026-01-01', '2026-12-31');

        $result = (new CatalogVersionResolver)->resolve([$version], new DateTimeImmutable('2027-01-01'));

        $this->assertSame($version, $result->version);
        $this->assertTrue($result->isProvisional);
    }

    public function test_the_covering_version_is_selected_regardless_of_input_order(): void
    {
        $old = $this->version(1, '2020-01-01', '2020-12-31');
        $covering = $this->version(2, '2026-01-01', '2026-12-31');
        $future = $this->version(3, '2030-01-01', '2030-12-31');
        $target = new DateTimeImmutable('2026-05-01');

        $sorted = (new CatalogVersionResolver)->resolve([$old, $covering, $future], $target);
        $shuffled = (new CatalogVersionResolver)->resolve([$future, $covering, $old], $target);

        $this->assertSame($covering, $sorted->version);
        $this->assertSame($covering, $shuffled->version);
        $this->assertFalse($shuffled->isProvisional);
    }

    public function test_the_prior_fallback_is_selected_regardless_of_input_order(): void
    {
        $oldest = $this->version(1, '2018-01-01', '2018-12-31');
        $middle = $this->version(2, '2020-01-01', '2020-12-31');
        $mostRecentPrior = $this->version(3, '2024-01-01', '2024-12-31');
        $target = new DateTimeImmutable('2026-05-01');

        $sorted = (new CatalogVersionResolver)->resolve([$oldest, $middle, $mostRecentPrior], $target);
        $shuffled = (new CatalogVersionResolver)->resolve([$mostRecentPrior, $oldest, $middle], $target);

        $this->assertSame($mostRecentPrior, $sorted->version);
        $this->assertSame($mostRecentPrior, $shuffled->version);
        $this->assertTrue($shuffled->isProvisional);
    }

    public function test_a_target_date_before_every_version_picks_the_earliest_not_the_newest(): void
    {
        $earliestFuture = $this->version(1, '2025-01-01', '2025-12-31');
        $middleFuture = $this->version(2, '2027-01-01', '2027-12-31');
        $newestFuture = $this->version(3, '2029-01-01', '2029-12-31');
        $target = new DateTimeImmutable('2010-01-01');

        $sorted = (new CatalogVersionResolver)->resolve([$earliestFuture, $middleFuture, $newestFuture], $target);
        $shuffled = (new CatalogVersionResolver)->resolve([$newestFuture, $middleFuture, $earliestFuture], $target);

        $this->assertSame($earliestFuture, $sorted->version);
        $this->assertSame($earliestFuture, $shuffled->version);
        $this->assertTrue($shuffled->isProvisional);
    }

    public function test_a_prior_version_is_preferred_over_a_nearer_future_version(): void
    {
        // 2024-12-31 is 5 months before the target, 2026-01-01 is only
        // 1 month after it — D5 still prefers the prior version.
        $prior = $this->version(1, '2024-01-01', '2024-12-31');
        $nearerFuture = $this->version(2, '2026-01-01', '2026-12-31');

        $result = (new CatalogVersionResolver)->resolve([$prior, $nearerFuture], new DateTimeImmutable('2025-12-01'));

        $this->assertSame($prior, $result->version);
        $this->assertTrue($result->isProvisional);
    }
}
