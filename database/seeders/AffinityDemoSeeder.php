<?php

namespace Database\Seeders;

use App\Models\AcademicCredential;
use App\Models\AcademicTerm;
use App\Models\Career;
use App\Models\Course;
use App\Models\CourseGroup;
use App\Models\Specialty;
use App\Models\Teacher;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Src\Academic\AcademicCredential\Domain\DegreeLevel;
use Src\Academic\AffinityCatalog\Application\DTOs\AffinityCatalogVersionDTO;
use Src\Academic\AffinityCatalog\Application\UseCases\CreateAffinityCatalogVersionUseCase;
use Src\Academic\TeacherAssignment\Application\DTOs\AttachTechnicalNoteDTO;
use Src\Academic\TeacherAssignment\Application\DTOs\ProposeTeacherAssignmentDTO;
use Src\Academic\TeacherAssignment\Application\UseCases\AttachTechnicalNoteUseCase;
use Src\Academic\TeacherAssignment\Application\UseCases\ProposeTeacherAssignmentUseCase;
use Src\Academic\TeacherAssignment\Domain\Contracts\UploadedDocument;

/**
 * Development-only demo data for DO-01/DO-02/DO-02a/DO-02b/DO-02d,
 * built through the real use cases (not raw inserts) so seeding this
 * doubles as an end-to-end smoke test of the whole verification
 * pipeline. Deterministic: a matched case, a not-matched case escalated
 * to a Technical Note, and a "no catalog" case (Phase 9,
 * IMPLEMENT_ACADEMIC_AFFINITIES.md).
 */
class AffinityDemoSeeder extends Seeder
{
    public function run(): void
    {
        $career = Career::query()->firstOrCreate(['nombre' => 'Ingeniería del Software - Tecnologías Informáticas'], ['activa' => true]);

        $webCourse = Course::query()->firstOrCreate(
            ['codigo' => 'ISW-521'],
            ['carrera_id' => $career->id, 'nombre' => 'Programación en Ambiente Web I', 'activo' => true],
        );
        $noCatalogCourse = Course::query()->firstOrCreate(
            ['codigo' => 'ISW-430'],
            ['carrera_id' => $career->id, 'nombre' => 'Estructuras de Datos', 'activo' => true],
        );

        $term = AcademicTerm::query()->firstOrCreate(['anio' => 2026, 'cuatrimestre' => 2], [
            'fecha_inicio' => '2026-05-01',
            'fecha_fin' => '2026-08-28',
        ]);

        $groupWeb = CourseGroup::query()->firstOrCreate(
            ['curso_id' => $webCourse->id, 'periodo_academico_id' => $term->id, 'numero' => 1],
            ['meta_id' => DB::table('metas')->value('id'), 'modalidad_id' => DB::table('modalidades')->value('id'), 'cupo' => 30],
        );
        $groupNoCatalog = CourseGroup::query()->firstOrCreate(
            ['curso_id' => $noCatalogCourse->id, 'periodo_academico_id' => $term->id, 'numero' => 1],
            ['meta_id' => DB::table('metas')->value('id'), 'modalidad_id' => DB::table('modalidades')->value('id'), 'cupo' => 30],
        );

        $softwareSpecialty = Specialty::query()->firstOrCreate(['nombre' => 'Ingeniería del Software - Tecnologías Informáticas']);
        $unrelatedSpecialty = Specialty::query()->firstOrCreate(['nombre' => 'Administración Aduanera']);

        if (! DB::table('catalogos_atinencia')->where('curso_id', $webCourse->id)->exists()) {
            /** @var CreateAffinityCatalogVersionUseCase $createCatalogVersion */
            $createCatalogVersion = app(CreateAffinityCatalogVersionUseCase::class);

            $createCatalogVersion->handle(new AffinityCatalogVersionDTO(
                courseId: $webCourse->id,
                councilAgreement: 'Acuerdo Consejo Universitario 12-2024',
                gazetteNumber: '45',
                effectiveStartDate: '2024-01-01',
                effectiveEndDate: '2025-12-31',
                specialtyIds: [$softwareSpecialty->id],
            ), null);

            $createCatalogVersion->handle(new AffinityCatalogVersionDTO(
                courseId: $webCourse->id,
                councilAgreement: 'Acuerdo Consejo Universitario 30-2026',
                gazetteNumber: '112',
                effectiveStartDate: '2026-01-01',
                effectiveEndDate: null,
                specialtyIds: [$softwareSpecialty->id],
            ), null);
        }

        if (DB::table('asignaciones_docentes')->where('grupo_id', $groupWeb->id)->exists()) {
            return;
        }

        $matchingTeacher = Teacher::factory()->create();
        AcademicCredential::query()->create([
            'docente_id' => $matchingTeacher->id,
            'especialidad_id' => $softwareSpecialty->id,
            'grado' => DegreeLevel::Licentiate,
            'institucion' => 'Universidad Técnica Nacional',
            'fecha_inicio' => '2013-03-01',
            'fecha_fin' => '2018-12-15',
        ]);

        $nonMatchingTeacher = Teacher::factory()->create();
        AcademicCredential::query()->create([
            'docente_id' => $nonMatchingTeacher->id,
            'especialidad_id' => $unrelatedSpecialty->id,
            'grado' => DegreeLevel::Bachelor,
            'institucion' => 'Universidad de Costa Rica',
            'fecha_inicio' => '2010-03-01',
            'fecha_fin' => '2015-12-15',
        ]);

        $noCatalogTeacher = Teacher::factory()->create();
        AcademicCredential::query()->create([
            'docente_id' => $noCatalogTeacher->id,
            'especialidad_id' => $softwareSpecialty->id,
            'grado' => DegreeLevel::Master,
            'institucion' => 'Instituto Tecnológico de Costa Rica',
            'fecha_inicio' => '2016-03-01',
            'fecha_fin' => '2020-12-15',
        ]);

        /** @var ProposeTeacherAssignmentUseCase $propose */
        $propose = app(ProposeTeacherAssignmentUseCase::class);

        $propose->handle(new ProposeTeacherAssignmentDTO(
            courseGroupId: $groupWeb->id,
            teacherId: $matchingTeacher->id,
            courseId: $webCourse->id,
            targetDate: $term->start_date->toDateString(),
        ), null);

        $notMatchedResult = $propose->handle(new ProposeTeacherAssignmentDTO(
            courseGroupId: $groupWeb->id,
            teacherId: $nonMatchingTeacher->id,
            courseId: $webCourse->id,
            targetDate: $term->start_date->toDateString(),
        ), null);

        $propose->handle(new ProposeTeacherAssignmentDTO(
            courseGroupId: $groupNoCatalog->id,
            teacherId: $noCatalogTeacher->id,
            courseId: $noCatalogCourse->id,
            targetDate: $term->start_date->toDateString(),
        ), null);

        $pdfPath = 'technical-notes/demo-criterio-tecnico.pdf';
        Storage::disk('local')->put($pdfPath, '%PDF-1.4 demo technical criterion for seeded data');

        /** @var AttachTechnicalNoteUseCase $attachNote */
        $attachNote = app(AttachTechnicalNoteUseCase::class);
        $attachNote->handle(new AttachTechnicalNoteDTO(
            teacherAssignmentId: $notMatchedResult->assignment->id(),
            ratificationDeadline: now()->addDays(30)->toDateString(),
            document: new UploadedDocument(
                storagePath: $pdfPath,
                originalFileName: 'criterio-tecnico-demo.pdf',
                mimeType: 'application/pdf',
                sizeBytes: Storage::disk('local')->size($pdfPath),
                hashSha256: hash('sha256', 'demo-criterio-tecnico'),
            ),
        ), null);
    }
}
