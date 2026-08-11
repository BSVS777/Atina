<?php

namespace Tests\Feature\Academic;

use App\Models\AcademicCredential;
use App\Models\AcademicTerm;
use App\Models\AffinityCatalogVersion as AffinityCatalogVersionModel;
use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\Specialty;
use App\Models\Teacher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Src\Academic\AffinityCatalog\Application\DTOs\AffinityCatalogVersionDTO;
use Src\Academic\AffinityCatalog\Application\UseCases\CreateAffinityCatalogVersionUseCase;
use Src\Academic\TeacherAssignment\Application\DTOs\ProposeTeacherAssignmentDTO;
use Src\Academic\TeacherAssignment\Application\UseCases\ProposeTeacherAssignmentUseCase;
use Src\Academic\TeacherAssignment\Domain\ProposalStatus;
use Src\Academic\TeacherAssignment\Domain\VerificationResult;
use Tests\TestCase;

/**
 * DO-02a: the automatic, synchronous verification and its three
 * possible synchronous outcomes (Atinente / No Atinente / Sin catálogo).
 */
class TeacherAssignmentVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_teacher_with_an_affine_credential_is_matched_and_confirmed(): void
    {
        $specialty = Specialty::factory()->create();
        $course = Course::factory()->create();
        $term = AcademicTerm::factory()->create(['start_date' => '2026-05-01']);
        $group = CourseGroup::factory()->create(['course_id' => $course->id, 'academic_term_id' => $term->id]);

        app(CreateAffinityCatalogVersionUseCase::class)->handle(new AffinityCatalogVersionDTO(
            courseId: $course->id,
            councilAgreement: 'Acuerdo 1-2026',
            gazetteNumber: '10',
            effectiveStartDate: '2026-01-01',
            effectiveEndDate: null,
            specialtyIds: [$specialty->id],
        ), null);

        $teacher = Teacher::factory()->create();
        AcademicCredential::factory()->create(['docente_id' => $teacher->id, 'especialidad_id' => $specialty->id]);

        $result = app(ProposeTeacherAssignmentUseCase::class)->handle(new ProposeTeacherAssignmentDTO(
            courseGroupId: $group->id,
            teacherId: $teacher->id,
            courseId: $course->id,
            targetDate: '2026-05-01',
        ), null);

        $this->assertSame(VerificationResult::Matched, $result->verification->result());
        $this->assertSame(ProposalStatus::Confirmed, $result->assignment->status());
        $this->assertNotNull($result->verification->catalogVersionId());
        $this->assertFalse($result->verification->isProvisional());
    }

    public function test_a_teacher_without_an_affine_credential_is_not_matched_and_stays_blocked(): void
    {
        $catalogSpecialty = Specialty::factory()->create();
        $teacherSpecialty = Specialty::factory()->create();
        $course = Course::factory()->create();
        $term = AcademicTerm::factory()->create(['start_date' => '2026-05-01']);
        $group = CourseGroup::factory()->create(['course_id' => $course->id, 'academic_term_id' => $term->id]);

        app(CreateAffinityCatalogVersionUseCase::class)->handle(new AffinityCatalogVersionDTO(
            courseId: $course->id,
            councilAgreement: 'Acuerdo 1-2026',
            gazetteNumber: '10',
            effectiveStartDate: '2026-01-01',
            effectiveEndDate: null,
            specialtyIds: [$catalogSpecialty->id],
        ), null);

        $teacher = Teacher::factory()->create();
        AcademicCredential::factory()->create(['docente_id' => $teacher->id, 'especialidad_id' => $teacherSpecialty->id]);

        $result = app(ProposeTeacherAssignmentUseCase::class)->handle(new ProposeTeacherAssignmentDTO(
            courseGroupId: $group->id,
            teacherId: $teacher->id,
            courseId: $course->id,
            targetDate: '2026-05-01',
        ), null);

        $this->assertSame(VerificationResult::NotMatched, $result->verification->result());
        $this->assertSame(ProposalStatus::Proposed, $result->assignment->status());
    }

    public function test_a_course_with_no_catalog_produces_no_catalog_result(): void
    {
        $course = Course::factory()->create();
        $term = AcademicTerm::factory()->create(['start_date' => '2026-05-01']);
        $group = CourseGroup::factory()->create(['course_id' => $course->id, 'academic_term_id' => $term->id]);
        $teacher = Teacher::factory()->create();

        $result = app(ProposeTeacherAssignmentUseCase::class)->handle(new ProposeTeacherAssignmentDTO(
            courseGroupId: $group->id,
            teacherId: $teacher->id,
            courseId: $course->id,
            targetDate: '2026-05-01',
        ), null);

        $this->assertSame(VerificationResult::NoCatalog, $result->verification->result());
        $this->assertNull($result->verification->catalogVersionId());
        $this->assertSame(ProposalStatus::Proposed, $result->assignment->status());
    }

    public function test_historical_verification_keeps_the_catalog_version_that_applied_at_the_time(): void
    {
        $specialty = Specialty::factory()->create();
        $course = Course::factory()->create();
        $term = AcademicTerm::factory()->create(['start_date' => '2026-05-01']);
        $group = CourseGroup::factory()->create(['course_id' => $course->id, 'academic_term_id' => $term->id]);

        $createCatalogVersion = app(CreateAffinityCatalogVersionUseCase::class);
        $firstVersion = $createCatalogVersion->handle(new AffinityCatalogVersionDTO(
            courseId: $course->id,
            councilAgreement: 'Acuerdo 1-2026',
            gazetteNumber: '10',
            effectiveStartDate: '2026-01-01',
            effectiveEndDate: '2026-12-31',
            specialtyIds: [$specialty->id],
        ), null);

        $teacher = Teacher::factory()->create();
        AcademicCredential::factory()->create(['docente_id' => $teacher->id, 'especialidad_id' => $specialty->id]);

        $result = app(ProposeTeacherAssignmentUseCase::class)->handle(new ProposeTeacherAssignmentDTO(
            courseGroupId: $group->id,
            teacherId: $teacher->id,
            courseId: $course->id,
            targetDate: '2026-05-01',
        ), null);

        $this->assertSame($firstVersion->id(), $result->verification->catalogVersionId());

        // D10: publishing a NEW version afterwards must not retroactively
        // change the already-recorded verification's catalog reference.
        $createCatalogVersion->handle(new AffinityCatalogVersionDTO(
            courseId: $course->id,
            councilAgreement: 'Acuerdo 2-2026',
            gazetteNumber: '20',
            effectiveStartDate: '2027-01-01',
            effectiveEndDate: null,
            specialtyIds: [$specialty->id],
        ), null);

        $this->assertDatabaseHas('verificaciones_atinencia', [
            'asignacion_docente_id' => $result->assignment->id(),
            'catalogo_atinencia_id' => $firstVersion->id(),
        ]);
        $this->assertSame(2, AffinityCatalogVersionModel::query()->where('curso_id', $course->id)->count());
    }
}
