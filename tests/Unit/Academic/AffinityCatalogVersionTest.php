<?php

namespace Tests\Unit\Academic;

use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Src\Academic\AffinityCatalog\Domain\Entities\AffinityCatalogVersion;

/**
 * Construction invariants and validity-range semantics of one catalog
 * entry (DO-02). `isAffineToSpecialty()` is covered separately by
 * AffinityCatalogVersionMatchingTest.
 */
class AffinityCatalogVersionTest extends TestCase
{
    /**
     * @param  array<int, int>  $specialtyIds
     */
    private function version(string $start, ?string $end, array $specialtyIds = [1]): AffinityCatalogVersion
    {
        return new AffinityCatalogVersion(
            id: 1,
            courseId: 1,
            versionNumber: 1,
            councilAgreement: 'Acuerdo 1-2026',
            gazetteNumber: '10',
            effectiveStartDate: new DateTimeImmutable($start),
            effectiveEndDate: $end !== null ? new DateTimeImmutable($end) : null,
            specialtyIds: $specialtyIds,
        );
    }

    public function test_a_blank_council_agreement_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new AffinityCatalogVersion(
            id: null,
            courseId: 1,
            versionNumber: 1,
            councilAgreement: '   ',
            gazetteNumber: '10',
            effectiveStartDate: new DateTimeImmutable('2026-01-01'),
            effectiveEndDate: null,
            specialtyIds: [1],
        );
    }

    public function test_a_blank_gazette_number_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new AffinityCatalogVersion(
            id: null,
            courseId: 1,
            versionNumber: 1,
            councilAgreement: 'Acuerdo 1-2026',
            gazetteNumber: '',
            effectiveStartDate: new DateTimeImmutable('2026-01-01'),
            effectiveEndDate: null,
            specialtyIds: [1],
        );
    }

    public function test_an_end_date_before_the_start_date_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->version('2026-06-01', '2026-01-01');
    }

    public function test_a_single_day_validity_range_is_accepted(): void
    {
        $version = $this->version('2026-06-01', '2026-06-01');

        $this->assertTrue($version->coversDate(new DateTimeImmutable('2026-06-01')));
    }

    public function test_a_version_without_affine_specialties_is_rejected(): void
    {
        // An entry that lists no specialty could never make anyone
        // affine — DO-02 requires at least one.
        $this->expectException(InvalidArgumentException::class);

        $this->version('2026-01-01', null, specialtyIds: []);
    }

    public function test_no_specialty_is_affine_when_the_listed_set_holds_a_single_unrelated_id(): void
    {
        $version = $this->version('2026-01-01', null, specialtyIds: [7]);

        $this->assertFalse($version->isAffineToSpecialty(1));
        $this->assertFalse($version->isAffineToSpecialty(70));
    }

    public function test_the_start_date_boundary_is_inclusive(): void
    {
        $version = $this->version('2026-01-01', '2026-12-31');

        $this->assertTrue($version->coversDate(new DateTimeImmutable('2026-01-01')));
        $this->assertFalse($version->coversDate(new DateTimeImmutable('2025-12-31')));
    }

    public function test_the_end_date_boundary_is_inclusive(): void
    {
        $version = $this->version('2026-01-01', '2026-12-31');

        $this->assertTrue($version->coversDate(new DateTimeImmutable('2026-12-31')));
        $this->assertFalse($version->coversDate(new DateTimeImmutable('2027-01-01')));
    }

    public function test_an_open_ended_version_covers_every_date_from_its_start_onwards(): void
    {
        $version = $this->version('2026-01-01', null);

        $this->assertTrue($version->coversDate(new DateTimeImmutable('2099-01-01')));
        $this->assertFalse($version->coversDate(new DateTimeImmutable('2025-12-31')));
    }

    public function test_a_range_starting_the_day_after_an_existing_one_ends_does_not_overlap(): void
    {
        $existing = $this->version('2024-01-01', '2024-12-31');

        $this->assertFalse($existing->overlapsRange(new DateTimeImmutable('2025-01-01'), new DateTimeImmutable('2025-12-31')));
    }

    public function test_a_range_starting_on_the_existing_end_date_overlaps(): void
    {
        $existing = $this->version('2024-01-01', '2024-12-31');

        $this->assertTrue($existing->overlapsRange(new DateTimeImmutable('2024-12-31'), null));
    }

    public function test_a_range_sharing_the_existing_start_date_overlaps(): void
    {
        $existing = $this->version('2024-01-01', '2024-12-31');

        $this->assertTrue($existing->overlapsRange(new DateTimeImmutable('2024-01-01'), new DateTimeImmutable('2024-03-31')));
    }

    public function test_a_range_fully_contained_in_the_existing_one_overlaps(): void
    {
        $existing = $this->version('2024-01-01', '2024-12-31');

        $this->assertTrue($existing->overlapsRange(new DateTimeImmutable('2024-05-01'), new DateTimeImmutable('2024-06-01')));
    }

    public function test_a_range_fully_containing_the_existing_one_overlaps(): void
    {
        $existing = $this->version('2024-05-01', '2024-06-01');

        $this->assertTrue($existing->overlapsRange(new DateTimeImmutable('2024-01-01'), new DateTimeImmutable('2024-12-31')));
    }

    public function test_an_open_ended_existing_version_overlaps_any_later_range(): void
    {
        $existing = $this->version('2024-01-01', null);

        $this->assertTrue($existing->overlapsRange(new DateTimeImmutable('2099-01-01'), null));
        $this->assertFalse($existing->overlapsRange(new DateTimeImmutable('2020-01-01'), new DateTimeImmutable('2023-12-31')));
    }

    public function test_assigning_an_id_preserves_every_other_attribute(): void
    {
        $version = new AffinityCatalogVersion(
            id: null,
            courseId: 4,
            versionNumber: 3,
            councilAgreement: 'Acuerdo 9-2026',
            gazetteNumber: '77',
            effectiveStartDate: new DateTimeImmutable('2026-01-01'),
            effectiveEndDate: new DateTimeImmutable('2026-12-31'),
            specialtyIds: [5, 6],
        );

        $persisted = $version->withId(42);

        $this->assertSame(42, $persisted->id());
        $this->assertSame(4, $persisted->courseId());
        $this->assertSame(3, $persisted->versionNumber());
        $this->assertSame('Acuerdo 9-2026', $persisted->councilAgreement());
        $this->assertSame('77', $persisted->gazetteNumber());
        $this->assertSame([5, 6], $persisted->specialtyIds());
        $this->assertNull($version->id(), 'withId() must not mutate the original instance.');
    }
}
