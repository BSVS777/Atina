<?php

namespace Tests\Feature\IdentityAccess;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Src\IdentityAccess\Role\Presentation\Livewire\RoleComponent;
use Src\Shared\Export\Contracts\PdfExporterInterface;
use Tests\Feature\Academic\Fakes\CapturingPdfExporter;
use Tests\TestCase;

/**
 * RoleComponent::exportPdf() existed with no automated coverage at all —
 * not even the authorization gate — before this test. Mirrors the
 * Teacher/AffinityCatalog/TeacherAssignment export test pattern.
 */
class RoleExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_with_permission_can_download_the_pdf(): void
    {
        $this->actingAs($this->userWithRoleExportPermission());

        $fake = new CapturingPdfExporter;
        $this->app->instance(PdfExporterInterface::class, $fake);

        Livewire::test(RoleComponent::class)
            ->call('exportPdf')
            ->assertFileDownloaded('roles.pdf', contentType: 'application/pdf');

        $this->assertStringContainsString(__('Roles'), $fake->html);
    }

    public function test_a_user_without_permission_cannot_download_the_pdf(): void
    {
        // RoleComponent::mount() itself gates on 'viewAny' (roles.view), so
        // this user needs that much to reach the page at all — proving
        // exportPdf() has its own, narrower gate (roles.export_pdf), not
        // just inheriting viewAny's.
        $this->actingAs($this->userWithRoleViewOnlyPermission());

        Livewire::test(RoleComponent::class)
            ->call('exportPdf')
            ->assertForbidden();
    }

    /**
     * Leaves the real PdfExporterInterface binding (SpatiePdfExporter →
     * DOMPDF) in place, proving the actual report template renders to a
     * real, valid PDF for this export surface too — not just Teachers.
     */
    public function test_the_real_pdf_pipeline_renders_a_valid_pdf(): void
    {
        $this->actingAs($this->userWithRoleExportPermission());

        $response = Livewire::test(RoleComponent::class)->call('exportPdf');

        $response->assertFileDownloaded('roles.pdf', contentType: 'application/pdf');

        $pdfBytes = base64_decode(data_get($response->effects, 'download.content'));

        $this->assertStringStartsWith('%PDF-', $pdfBytes);
    }

    private function userWithRoleExportPermission(): User
    {
        $user = User::factory()->create();

        foreach (['view', 'export_pdf'] as $action) {
            $permission = Permission::query()->firstOrCreate(
                ['name' => "roles.{$action}"],
                ['module' => 'roles', 'action' => $action],
            );
            $user->givePermissionTo($permission->name);
        }

        return $user;
    }

    private function userWithRoleViewOnlyPermission(): User
    {
        $user = User::factory()->create();

        $permission = Permission::query()->firstOrCreate(
            ['name' => 'roles.view'],
            ['module' => 'roles', 'action' => 'view'],
        );
        $user->givePermissionTo($permission->name);

        return $user;
    }
}
