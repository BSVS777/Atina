<?php

namespace Tests\Feature\Academic;

use App\Models\Permission;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Src\Academic\Teacher\Presentation\Livewire\TeacherComponent;
use Src\Shared\Export\Contracts\ExcelExporterInterface;
use Src\Shared\Export\Contracts\PdfExporterInterface;
use Tests\Feature\Academic\Fakes\CapturingExcelExporter;
use Tests\Feature\Academic\Fakes\CapturingPdfExporter;
use Tests\TestCase;

class TeacherExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_with_permission_can_download_the_pdf(): void
    {
        $this->actingAs($this->userWithTeacherManagementPermissions());
        Teacher::factory()->create();

        // The real PdfExporterInterface renders via headless Chrome
        // (Browsershot/Puppeteer), which isn't installed in this test
        // environment — the fake exercises the same authorize() + wiring
        // + Content-Type path without needing a real browser.
        $this->app->instance(PdfExporterInterface::class, new CapturingPdfExporter);

        Livewire::test(TeacherComponent::class)
            ->call('exportPdf')
            ->assertFileDownloaded('docentes.pdf', contentType: 'application/pdf');
    }

    public function test_a_user_with_permission_can_download_the_xlsx(): void
    {
        $this->actingAs($this->userWithTeacherManagementPermissions());
        Teacher::factory()->create();

        Livewire::test(TeacherComponent::class)
            ->call('exportExcel')
            ->assertFileDownloaded('docentes.xlsx', contentType: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_a_user_without_permission_cannot_download_the_pdf(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(TeacherComponent::class)
            ->call('exportPdf')
            ->assertForbidden();
    }

    public function test_a_user_without_permission_cannot_download_the_xlsx(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(TeacherComponent::class)
            ->call('exportExcel')
            ->assertForbidden();
    }

    public function test_the_export_respects_the_current_search(): void
    {
        $this->actingAs($this->userWithTeacherManagementPermissions());
        Teacher::factory()->create(['first_name' => 'Ana', 'last_name' => 'Rojas', 'second_last_name' => 'Vega']);
        Teacher::factory()->create(['first_name' => 'Beto', 'last_name' => 'Soto', 'second_last_name' => 'Leon']);

        $fake = new CapturingExcelExporter;
        $this->app->instance(ExcelExporterInterface::class, $fake);

        // Client mode: the on-screen search box lives in Alpine, so the
        // download button hands its current value straight to exportExcel()
        // as a positional argument (see data-table.blade.php) — never
        // through the server-bound $this->search property.
        Livewire::test(TeacherComponent::class)
            ->call('exportExcel', 'Rojas');

        $this->assertCount(1, $fake->rows);
        $this->assertSame('Ana Rojas Vega', $fake->rows[0][__('Name')]);
    }

    private function userWithTeacherManagementPermissions(): User
    {
        $user = User::factory()->create();

        $permission = Permission::query()->firstOrCreate(
            ['name' => 'usuarios.gestionar'],
            ['module' => 'usuarios', 'action' => 'gestionar'],
        );
        $user->givePermissionTo($permission->name);

        return $user;
    }
}
