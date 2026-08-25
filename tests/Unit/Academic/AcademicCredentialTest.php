<?php

namespace Tests\Unit\Academic;

use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Src\Academic\AcademicCredential\Domain\DegreeLevel;
use Src\Academic\AcademicCredential\Domain\Entities\AcademicCredential;
use Src\Academic\AcademicCredential\Domain\StudyPeriod;

class AcademicCredentialTest extends TestCase
{
    public function test_a_credential_without_an_institution_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->credential(institution: '   ');
    }

    public function test_assigning_an_id_preserves_every_other_attribute(): void
    {
        $credential = $this->credential();

        $persisted = $credential->withId(42);

        $this->assertSame(42, $persisted->id());
        $this->assertSame(1, $persisted->teacherId());
        $this->assertSame(10, $persisted->specialtyId());
        $this->assertSame(DegreeLevel::Master, $persisted->degreeLevel());
        $this->assertSame('Universidad Técnica Nacional', $persisted->institution());
        $this->assertSame('2010-03-01', $persisted->studyPeriod()->startDate()->format('Y-m-d'));
        $this->assertSame('2015-11-30', $persisted->studyPeriod()->endDate()->format('Y-m-d'));
        $this->assertNull($credential->id(), 'withId() must not mutate the original instance.');
    }

    private function credential(string $institution = 'Universidad Técnica Nacional'): AcademicCredential
    {
        return new AcademicCredential(
            id: null,
            teacherId: 1,
            specialtyId: 10,
            degreeLevel: DegreeLevel::Master,
            institution: $institution,
            studyPeriod: new StudyPeriod(
                new DateTimeImmutable('2010-03-01'),
                new DateTimeImmutable('2015-11-30'),
            ),
        );
    }
}
