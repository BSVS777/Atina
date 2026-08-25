<?php

namespace Tests\Unit\Academic;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Src\Academic\AcademicCredential\Domain\DegreeLevel;
use Src\Academic\AcademicCredential\Domain\Entities\AcademicCredential;
use Src\Academic\AcademicCredential\Domain\YearObtained;

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
        $this->assertSame('National Technical University', $persisted->institution());
        $this->assertSame(2015, $persisted->yearObtained()->value());
        $this->assertNull($credential->id(), 'withId() must not mutate the original instance.');
    }

    private function credential(string $institution = 'National Technical University'): AcademicCredential
    {
        return new AcademicCredential(
            id: null,
            teacherId: 1,
            specialtyId: 10,
            degreeLevel: DegreeLevel::Master,
            institution: $institution,
            yearObtained: new YearObtained(2015),
        );
    }
}
