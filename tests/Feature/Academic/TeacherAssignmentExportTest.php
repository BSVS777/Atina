<?php

namespace Tests\Feature\Academic;

use App\Models\AcademicTerm;
use App\Models\AffinityCatalogVersion;
use App\Models\AffinityVerification;
use App\Models\CourseGroup;
use App\Models\Permission;
use App\Models\Teacher;
use App\Models\TeacherAssignment as TeacherAssignmentModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Src\Academic\TeacherAssignment\Domain\VerificationResult;
use Src\Academic\TeacherAssignment\Presentation\Livewire\TeacherAssignmentComponent;
use Src\Shared\Export\Contracts\ExcelExporterInterface;
use Src\Shared\Export\Contracts\PdfExporterInterface;
use Tests\Feature\Academic\Fakes\CapturingExcelExporter;
use Tests\Feature\Academic\Fakes\CapturingPdfExporter;
use Tests\TestCase;

class TeacherAssignmentExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_with_permission_can_download_the_pdf(): void
    {
        $this->actingAs($this->userWithVerificationPermissions());

        // The real PdfExporterInterface (SpatiePdfExporter → DOMPDF) would
        // work fine here too (see SpatiePdfExporterTest and
        // TeacherExportTest for real-pipeline coverage of this shared
        // infrastructure) — the fake is used here so the assertion can
        // grep plain HTML instead of a PDF binary.
        $this->app->instance(PdfExporterInterface::class, new CapturingPdfExporter);

        Livewire::test(TeacherAssignmentComponent::class)
            ->call('exportPdf')
            ->assertFileDownloaded('verificacion-de-atinencia-docente.pdf', contentType: 'application/pdf');
    }

    public function test_a_user_with_permission_can_download_the_xlsx(): void
    {
        $this->actingAs($this->userWithVerificationPermissions());

        Livewire::test(TeacherAssignmentComponent::class)
            ->call('exportExcel')
            ->assertFileDownloaded('verificacion-de-atinencia-docente.xlsx', contentType: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_a_user_without_permission_cannot_download_the_pdf(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(TeacherAssignmentComponent::class)
            ->call('exportPdf')
            ->assertForbidden();
    }

    public function test_a_user_without_permission_cannot_download_the_xlsx(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(TeacherAssignmentComponent::class)
            ->call('exportExcel')
            ->assertForbidden();
    }

    public function test_the_export_respects_the_current_search(): void
    {
        $user = $this->userWithVerificationPermissions();
        $teacherA = Teacher::factory()->create(['first_name' => 'Ana', 'last_name' => 'Rojas']);
        $teacherB = Teacher::factory()->create(['first_name' => 'Beto', 'last_name' => 'Soto']);
        $term = AcademicTerm::factory()->create();
        $group = CourseGroup::factory()->create(['academic_term_id' => $term->id]);
        $this->actingAs($user);

        // No catalog exists for the group's course, so both proposals
        // resolve to "Sin catálogo" — irrelevant here, only the teacher
        // name needs to differ for the search filter under test.
        Livewire::test(TeacherAssignmentComponent::class)
            ->set('proposeForm.teacherId', $teacherA->id)
            ->set('proposeForm.courseGroupId', $group->id)
            ->call('propose');

        Livewire::test(TeacherAssignmentComponent::class)
            ->set('proposeForm.teacherId', $teacherB->id)
            ->set('proposeForm.courseGroupId', $group->id)
            ->call('propose');

        $fake = new CapturingExcelExporter;
        $this->app->instance(ExcelExporterInterface::class, $fake);

        // Server mode: the search box is wire:model-bound, so no explicit
        // value is passed to exportExcel() — $this->search is already live.
        Livewire::test(TeacherAssignmentComponent::class)
            ->set('search', 'Rojas')
            ->call('exportExcel');

        $this->assertCount(1, $fake->rows);
        $this->assertStringContainsString('Ana Rojas', $fake->rows[0][__('Teacher')]);
    }

    public function test_the_export_shows_the_justification_when_present(): void
    {
        $user = $this->userWithVerificationPermissions();
        $teacher = Teacher::factory()->create();
        $term = AcademicTerm::factory()->create();
        $group = CourseGroup::factory()->create(['academic_term_id' => $term->id]);
        $this->actingAs($user);

        Livewire::test(TeacherAssignmentComponent::class)
            ->set('proposeForm.teacherId', $teacher->id)
            ->set('proposeForm.courseGroupId', $group->id)
            ->call('propose');

        $assignment = TeacherAssignmentModel::query()->where('docente_id', $teacher->id)->firstOrFail();

        // Simulates a DO-02d manual decision appending a newer verification
        // row with a free-text justification (the automatic proposal's own
        // verification never sets one) — the repository picks the row with
        // the latest created_at/id as the assignment's latestVerification.
        AffinityVerification::factory()->create([
            'asignacion_docente_id' => $assignment->id,
            'catalogo_atinencia_id' => null,
            'resultado' => VerificationResult::NoCatalog,
            'justificacion' => 'Aprobado manualmente por la coordinadora.',
            'created_at' => now()->addMinute(),
        ]);

        $fake = new CapturingExcelExporter;
        $this->app->instance(ExcelExporterInterface::class, $fake);

        Livewire::test(TeacherAssignmentComponent::class)->call('exportExcel');

        $this->assertCount(1, $fake->rows);
        $this->assertSame(
            'Aprobado manualmente por la coordinadora.',
            $fake->rows[0][__('Catalog / justification')],
        );
    }

    public function test_the_export_falls_back_to_the_catalog_citation_when_there_is_no_justification(): void
    {
        $user = $this->userWithVerificationPermissions();
        $teacher = Teacher::factory()->create();
        $term = AcademicTerm::factory()->create();
        $group = CourseGroup::factory()->create(['academic_term_id' => $term->id]);
        $catalog = AffinityCatalogVersion::factory()->create([
            'curso_id' => $group->course_id,
            'version' => 9,
            'acuerdo' => 'Acuerdo de prueba',
            'numero_gaceta' => '42',
        ]);
        $this->actingAs($user);

        Livewire::test(TeacherAssignmentComponent::class)
            ->set('proposeForm.teacherId', $teacher->id)
            ->set('proposeForm.courseGroupId', $group->id)
            ->call('propose');

        $assignment = TeacherAssignmentModel::query()->where('docente_id', $teacher->id)->firstOrFail();

        AffinityVerification::factory()->create([
            'asignacion_docente_id' => $assignment->id,
            'catalogo_atinencia_id' => $catalog->id,
            'resultado' => VerificationResult::NotMatched,
            'justificacion' => null,
            'created_at' => now()->addMinute(),
        ]);

        $fake = new CapturingExcelExporter;
        $this->app->instance(ExcelExporterInterface::class, $fake);

        Livewire::test(TeacherAssignmentComponent::class)->call('exportExcel');

        $this->assertCount(1, $fake->rows);
        $this->assertSame(
            'v9 — Acuerdo de prueba / Gaceta 42',
            $fake->rows[0][__('Catalog / justification')],
        );
    }

    private function userWithVerificationPermissions(): User
    {
        $user = User::factory()->create();

        $permission = Permission::query()->firstOrCreate(
            ['name' => 'atinencia.verificar'],
            ['module' => 'atinencia', 'action' => 'verificar'],
        );
        $user->givePermissionTo($permission->name);

        return $user;
    }
}
