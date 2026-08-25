<?php

namespace Tests\Feature\Academic;

use App\Models\AffinityCatalogVersion;
use App\Models\Course;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Src\Academic\AffinityCatalog\Presentation\Livewire\AffinityCatalogComponent;
use Src\Shared\Export\Contracts\ExcelExporterInterface;
use Src\Shared\Export\Contracts\PdfExporterInterface;
use Tests\Feature\Academic\Fakes\CapturingExcelExporter;
use Tests\Feature\Academic\Fakes\CapturingPdfExporter;
use Tests\TestCase;

class AffinityCatalogExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_with_permission_can_download_the_pdf(): void
    {
        $this->actingAs($this->userWithCatalogManagementPermissions());
        $course = Course::factory()->create();
        AffinityCatalogVersion::factory()->create(['curso_id' => $course->id]);

        // The real PdfExporterInterface (SpatiePdfExporter → DOMPDF) would
        // work fine here too (see SpatiePdfExporterTest and
        // TeacherExportTest for real-pipeline coverage of this shared
        // infrastructure) — the fake is used here so the assertion can
        // grep plain HTML instead of a PDF binary.
        $this->app->instance(PdfExporterInterface::class, new CapturingPdfExporter);

        Livewire::test(AffinityCatalogComponent::class)
            ->set('selectedCourseId', $course->id)
            ->call('exportPdf')
            ->assertFileDownloaded('catalogo-de-atinencias.pdf', contentType: 'application/pdf');
    }

    public function test_a_user_with_permission_can_download_the_xlsx(): void
    {
        $this->actingAs($this->userWithCatalogManagementPermissions());
        $course = Course::factory()->create();
        AffinityCatalogVersion::factory()->create(['curso_id' => $course->id]);

        Livewire::test(AffinityCatalogComponent::class)
            ->set('selectedCourseId', $course->id)
            ->call('exportExcel')
            ->assertFileDownloaded('catalogo-de-atinencias.xlsx', contentType: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_a_user_without_permission_cannot_download_the_pdf(): void
    {
        $this->actingAs(User::factory()->create());
        $course = Course::factory()->create();

        Livewire::test(AffinityCatalogComponent::class)
            ->set('selectedCourseId', $course->id)
            ->call('exportPdf')
            ->assertForbidden();
    }

    public function test_a_user_without_permission_cannot_download_the_xlsx(): void
    {
        $this->actingAs(User::factory()->create());
        $course = Course::factory()->create();

        Livewire::test(AffinityCatalogComponent::class)
            ->set('selectedCourseId', $course->id)
            ->call('exportExcel')
            ->assertForbidden();
    }

    public function test_the_export_respects_the_current_search(): void
    {
        $this->actingAs($this->userWithCatalogManagementPermissions());
        $course = Course::factory()->create();
        AffinityCatalogVersion::factory()->create([
            'curso_id' => $course->id,
            'version' => 1,
            'acuerdo' => 'Acuerdo especial 1-2024',
        ]);
        AffinityCatalogVersion::factory()->create([
            'curso_id' => $course->id,
            'version' => 2,
            'acuerdo' => 'Convenio distinto 2-2025',
        ]);

        $fake = new CapturingExcelExporter;
        $this->app->instance(ExcelExporterInterface::class, $fake);

        // Server mode: the search box is wire:model-bound, so no explicit
        // value is passed to exportExcel() — $this->search is already live.
        Livewire::test(AffinityCatalogComponent::class)
            ->set('selectedCourseId', $course->id)
            ->set('search', 'especial')
            ->call('exportExcel');

        $this->assertCount(1, $fake->rows);
        $this->assertSame('Acuerdo especial 1-2024', $fake->rows[0][__('Council agreement')]);
    }

    private function userWithCatalogManagementPermissions(): User
    {
        $user = User::factory()->create();

        $permission = Permission::query()->firstOrCreate(
            ['name' => 'catalogo.gestionar'],
            ['module' => 'catalogo', 'action' => 'gestionar'],
        );
        $user->givePermissionTo($permission->name);

        return $user;
    }
}
