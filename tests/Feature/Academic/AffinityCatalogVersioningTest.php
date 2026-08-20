<?php

namespace Tests\Feature\Academic;

use App\Models\AffinityVerification;
use App\Models\Course;
use App\Models\Specialty;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Src\Academic\AffinityCatalog\Application\DTOs\AffinityCatalogVersionDTO;
use Src\Academic\AffinityCatalog\Application\UseCases\CreateAffinityCatalogVersionUseCase;
use Src\Academic\AffinityCatalog\Application\UseCases\ListAffinityCatalogVersionsForCourseUseCase;
use Src\Academic\AffinityCatalog\Application\UseCases\UpdateAffinityCatalogVersionUseCase;
use Src\Academic\AffinityCatalog\Domain\Exceptions\CatalogVersionInUseException;
use Src\Academic\AffinityCatalog\Domain\Exceptions\OverlappingCatalogVersionException;
use Tests\TestCase;

/**
 * DO-02: versioned catalog — every update is a new version, prior
 * versions are never deleted, and overlapping validity ranges for the
 * same course are rejected (D7).
 */
class AffinityCatalogVersioningTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_council_agreement_is_rejected(): void
    {
        $course = Course::factory()->create();
        $specialty = Specialty::factory()->create();

        $this->expectException(\InvalidArgumentException::class);

        app(CreateAffinityCatalogVersionUseCase::class)->handle(new AffinityCatalogVersionDTO(
            courseId: $course->id,
            councilAgreement: '',
            gazetteNumber: '10',
            effectiveStartDate: '2026-01-01',
            effectiveEndDate: null,
            specialtyIds: [$specialty->id],
        ), null);
    }

    public function test_missing_gazette_number_is_rejected(): void
    {
        $course = Course::factory()->create();
        $specialty = Specialty::factory()->create();

        $this->expectException(\InvalidArgumentException::class);

        app(CreateAffinityCatalogVersionUseCase::class)->handle(new AffinityCatalogVersionDTO(
            courseId: $course->id,
            councilAgreement: 'Acuerdo 1-2026',
            gazetteNumber: '',
            effectiveStartDate: '2026-01-01',
            effectiveEndDate: null,
            specialtyIds: [$specialty->id],
        ), null);
    }

    public function test_each_update_creates_a_new_version_without_deleting_the_previous_one(): void
    {
        $course = Course::factory()->create();
        $specialty = Specialty::factory()->create();
        $useCase = app(CreateAffinityCatalogVersionUseCase::class);

        $useCase->handle(new AffinityCatalogVersionDTO(
            courseId: $course->id,
            councilAgreement: 'Acuerdo 1-2024',
            gazetteNumber: '10',
            effectiveStartDate: '2024-01-01',
            effectiveEndDate: '2025-12-31',
            specialtyIds: [$specialty->id],
        ), null);

        $useCase->handle(new AffinityCatalogVersionDTO(
            courseId: $course->id,
            councilAgreement: 'Acuerdo 2-2026',
            gazetteNumber: '20',
            effectiveStartDate: '2026-01-01',
            effectiveEndDate: null,
            specialtyIds: [$specialty->id],
        ), null);

        $versions = app(ListAffinityCatalogVersionsForCourseUseCase::class)->handle($course->id);

        $this->assertCount(2, $versions);
        $this->assertEqualsCanonicalizing([1, 2], array_map(fn ($v) => $v->versionNumber(), $versions));
    }

    public function test_overlapping_validity_ranges_are_blocked(): void
    {
        $course = Course::factory()->create();
        $specialty = Specialty::factory()->create();
        $useCase = app(CreateAffinityCatalogVersionUseCase::class);

        $useCase->handle(new AffinityCatalogVersionDTO(
            courseId: $course->id,
            councilAgreement: 'Acuerdo 1-2024',
            gazetteNumber: '10',
            effectiveStartDate: '2024-01-01',
            effectiveEndDate: '2026-12-31',
            specialtyIds: [$specialty->id],
        ), null);

        $this->expectException(OverlappingCatalogVersionException::class);

        $useCase->handle(new AffinityCatalogVersionDTO(
            courseId: $course->id,
            councilAgreement: 'Acuerdo 2-2026',
            gazetteNumber: '20',
            effectiveStartDate: '2026-06-01',
            effectiveEndDate: null,
            specialtyIds: [$specialty->id],
        ), null);
    }

    public function test_a_version_with_no_verifications_yet_can_be_edited_to_fix_a_mistake(): void
    {
        $course = Course::factory()->create();
        $specialty = Specialty::factory()->create();
        $version = app(CreateAffinityCatalogVersionUseCase::class)->handle(new AffinityCatalogVersionDTO(
            courseId: $course->id,
            councilAgreement: 'Acuerdo 1-2024',
            gazetteNumber: '10',
            effectiveStartDate: '2024-01-01',
            effectiveEndDate: null,
            specialtyIds: [$specialty->id],
        ), null);

        $updated = app(UpdateAffinityCatalogVersionUseCase::class)->handle($version->id(), new AffinityCatalogVersionDTO(
            courseId: $course->id,
            councilAgreement: 'Acuerdo 1-2024 (corregido)',
            gazetteNumber: '10',
            effectiveStartDate: '2024-01-01',
            effectiveEndDate: null,
            specialtyIds: [$specialty->id],
        ), null);

        $this->assertSame($version->id(), $updated->id());
        $this->assertSame($version->versionNumber(), $updated->versionNumber());
        $this->assertSame('Acuerdo 1-2024 (corregido)', $updated->councilAgreement());

        $versions = app(ListAffinityCatalogVersionsForCourseUseCase::class)->handle($course->id);
        $this->assertCount(1, $versions);
    }

    public function test_a_version_already_cited_by_a_verification_cannot_be_edited(): void
    {
        $course = Course::factory()->create();
        $specialty = Specialty::factory()->create();
        $version = app(CreateAffinityCatalogVersionUseCase::class)->handle(new AffinityCatalogVersionDTO(
            courseId: $course->id,
            councilAgreement: 'Acuerdo 1-2024',
            gazetteNumber: '10',
            effectiveStartDate: '2024-01-01',
            effectiveEndDate: null,
            specialtyIds: [$specialty->id],
        ), null);

        AffinityVerification::factory()->create(['catalogo_atinencia_id' => $version->id()]);

        $this->expectException(CatalogVersionInUseException::class);

        app(UpdateAffinityCatalogVersionUseCase::class)->handle($version->id(), new AffinityCatalogVersionDTO(
            courseId: $course->id,
            councilAgreement: 'Acuerdo intento de edición',
            gazetteNumber: '10',
            effectiveStartDate: '2024-01-01',
            effectiveEndDate: null,
            specialtyIds: [$specialty->id],
        ), null);
    }

    public function test_editing_a_version_into_an_overlapping_range_is_blocked(): void
    {
        $course = Course::factory()->create();
        $specialty = Specialty::factory()->create();
        $useCase = app(CreateAffinityCatalogVersionUseCase::class);

        $useCase->handle(new AffinityCatalogVersionDTO(
            courseId: $course->id,
            councilAgreement: 'Acuerdo 1-2024',
            gazetteNumber: '10',
            effectiveStartDate: '2024-01-01',
            effectiveEndDate: '2025-12-31',
            specialtyIds: [$specialty->id],
        ), null);

        $second = $useCase->handle(new AffinityCatalogVersionDTO(
            courseId: $course->id,
            councilAgreement: 'Acuerdo 2-2026',
            gazetteNumber: '20',
            effectiveStartDate: '2026-01-01',
            effectiveEndDate: null,
            specialtyIds: [$specialty->id],
        ), null);

        $this->expectException(OverlappingCatalogVersionException::class);

        app(UpdateAffinityCatalogVersionUseCase::class)->handle($second->id(), new AffinityCatalogVersionDTO(
            courseId: $course->id,
            councilAgreement: 'Acuerdo 2-2026',
            gazetteNumber: '20',
            effectiveStartDate: '2025-06-01',
            effectiveEndDate: null,
            specialtyIds: [$specialty->id],
        ), null);
    }
}
