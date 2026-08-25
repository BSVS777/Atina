<?php

namespace Tests\Unit\Academic;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Src\Academic\AffinityCatalog\Domain\Entities\AffinityCatalogVersion;

/**
 * Professor-confirmed rule (2026-08-24, Docs/DIARIO_DECISIONES_IA.md):
 * affinity is decided by exact specialty-ID membership in the course's
 * applicable catalog entry — catalog membership is the sole source of
 * truth. A specialty related to the course's subject matter is not
 * automatically affine (the professor's own example: a Cybersecurity
 * specialist must not automatically match a Programming course merely
 * because the fields are related). DegreeLevel does not independently
 * determine affinity — `isAffineToSpecialty()` never reads it — and
 * there is no semantic/fuzzy/AI-based inference.
 */
class AffinityCatalogVersionMatchingTest extends TestCase
{
    /**
     * @param  array<int, int>  $specialtyIds
     */
    private function version(array $specialtyIds): AffinityCatalogVersion
    {
        return new AffinityCatalogVersion(
            id: 1,
            courseId: 1,
            versionNumber: 1,
            councilAgreement: 'Acuerdo 1-2026',
            gazetteNumber: '10',
            effectiveStartDate: new DateTimeImmutable('2026-01-01'),
            effectiveEndDate: null,
            specialtyIds: $specialtyIds,
        );
    }

    public function test_a_specialty_explicitly_listed_in_the_catalog_is_affine(): void
    {
        $programming = 1;

        $version = $this->version([$programming]);

        $this->assertTrue($version->isAffineToSpecialty($programming));
    }

    public function test_a_specialty_absent_from_the_catalog_is_not_affine(): void
    {
        $programming = 1;
        $unrelated = 2;

        $version = $this->version([$programming]);

        $this->assertFalse($version->isAffineToSpecialty($unrelated));
    }

    public function test_a_related_but_unlisted_specialty_is_not_affine(): void
    {
        // Professor's own example: Cybersecurity is a related field to
        // Programming, but not being explicitly listed in the course's
        // catalog entry must still produce No Atinente.
        $programming = 1;
        $cybersecurity = 2;

        $version = $this->version([$programming]);

        $this->assertFalse($version->isAffineToSpecialty($cybersecurity));
    }

    public function test_membership_is_an_exact_id_match_across_multiple_listed_specialties(): void
    {
        $version = $this->version([10, 20, 30]);

        $this->assertTrue($version->isAffineToSpecialty(20));
        $this->assertFalse($version->isAffineToSpecialty(2));
        $this->assertFalse($version->isAffineToSpecialty(200));
    }
}
