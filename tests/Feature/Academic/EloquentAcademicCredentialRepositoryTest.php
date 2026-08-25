<?php

namespace Tests\Feature\Academic;

use App\Models\AcademicCredential as AcademicCredentialModel;
use App\Models\Specialty;
use App\Models\Teacher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Src\Academic\AcademicCredential\Domain\DegreeLevel;
use Src\Academic\AcademicCredential\Domain\Exceptions\CorruptCredentialRecordException;
use Src\Academic\AcademicCredential\Infrastructure\Persistence\Repositories\EloquentAcademicCredentialRepository;
use Tests\TestCase;

/**
 * Regression coverage for the "Call to a member function format() on null"
 * crash caused by the atestados.fecha_inicio/fecha_fin migration not having
 * run yet on an environment (see 2026_08_25_205807_replace_year_obtained_with_study_period_in_atestados).
 * The columns are NOT NULL in the real schema, so this state is only
 * reachable through schema/deployment drift — this test simulates it
 * directly on an Eloquent model instance without touching the DB.
 */
class EloquentAcademicCredentialRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_fully_dated_credential_hydrates_correctly(): void
    {
        $teacher = Teacher::factory()->create();
        $specialty = Specialty::factory()->create();
        $model = AcademicCredentialModel::factory()->create([
            'docente_id' => $teacher->id,
            'especialidad_id' => $specialty->id,
            'grado' => DegreeLevel::Master,
            'fecha_inicio' => '2010-03-01',
            'fecha_fin' => '2015-11-30',
        ]);

        $credential = (new EloquentAcademicCredentialRepository)->find($model->id);

        $this->assertNotNull($credential);
        $this->assertSame('2010-03-01', $credential->studyPeriod()->startDate()->format('Y-m-d'));
        $this->assertSame('2015-11-30', $credential->studyPeriod()->endDate()->format('Y-m-d'));
    }

    public function test_a_record_missing_its_study_period_dates_raises_a_clear_exception_instead_of_a_fatal_error(): void
    {
        $model = new AcademicCredentialModel;
        $model->setRawAttributes([
            'id' => 999,
            'docente_id' => 1,
            'especialidad_id' => 1,
            'grado' => 'Maestría',
            'institucion' => 'UTN',
            'fecha_inicio' => null,
            'fecha_fin' => null,
        ]);

        $toDomain = new ReflectionMethod(EloquentAcademicCredentialRepository::class, 'toDomain');
        $toDomain->setAccessible(true);

        $this->expectException(CorruptCredentialRecordException::class);

        $toDomain->invoke(new EloquentAcademicCredentialRepository, $model);
    }
}
