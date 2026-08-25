<?php

namespace Tests\Feature\Academic;

use App\Models\Teacher;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Src\Academic\AcademicCredential\Domain\Entities\AcademicCredential;
use Src\Academic\AffinityCatalog\Domain\Entities\AffinityCatalogVersion;
use Src\Academic\TeacherAssignment\Domain\Entities\TeacherAssignment;
use Src\Academic\TeacherAssignment\Domain\Entities\TechnicalNote;
use Tests\TestCase;

/**
 * Professor-confirmed rule (2026-08-24, Docs/DIARIO_DECISIONES_IA.md):
 * "Administrador has access to everything." Exercises the real,
 * seeded "Administrador" role (`RoleSeeder::OFFICIAL_ROLE_PERMISSIONS`)
 * against every Academic policy ability, rather than a synthetic
 * single-permission user — this is what actually proves the role, not
 * just the permission strings it happens to be assigned in isolation.
 */
class AdministradorFullAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrador_role_can_perform_every_academic_operation(): void
    {
        $this->seed([PermissionSeeder::class, RoleSeeder::class]);

        $admin = User::factory()->create();
        $admin->assignRole('Administrador');

        $this->assertTrue($admin->can('create', Teacher::class), 'Administrador must be able to create teachers.');

        $this->assertTrue($admin->can('create', AcademicCredential::class), 'Administrador must be able to create academic credentials.');
        $this->assertTrue($admin->can('update', AcademicCredential::class), 'Administrador must be able to edit academic credentials.');

        $this->assertTrue($admin->can('create', AffinityCatalogVersion::class), 'Administrador must be able to create catalog versions.');
        $this->assertTrue($admin->can('update', AffinityCatalogVersion::class), 'Administrador must be able to edit catalog versions.');
        $this->assertTrue($admin->can('exportPdf', AffinityCatalogVersion::class));
        $this->assertTrue($admin->can('exportExcel', AffinityCatalogVersion::class));

        $this->assertTrue($admin->can('create', TeacherAssignment::class), 'Administrador must be able to propose a teacher assignment.');
        $this->assertTrue($admin->can('decide', TeacherAssignment::class), 'Administrador must be able to decide a "Sin catálogo" case.');
        $this->assertTrue($admin->can('exportPdf', TeacherAssignment::class));
        $this->assertTrue($admin->can('exportExcel', TeacherAssignment::class));

        $this->assertTrue($admin->can('create', TechnicalNote::class), 'Administrador must be able to register a Technical Note.');
        $this->assertTrue($admin->can('approve', TechnicalNote::class), 'Administrador must be able to ratify/reject a Technical Note.');
    }

    public function test_administrador_role_is_granted_every_official_academic_permission(): void
    {
        $this->seed([PermissionSeeder::class, RoleSeeder::class]);

        $admin = User::factory()->create();
        $admin->assignRole('Administrador');

        foreach (['atestados.gestionar', 'catalogo.gestionar', 'atinencia.verificar', 'nota_tecnica.aprobar', 'usuarios.gestionar'] as $permission) {
            $this->assertTrue($admin->hasPermissionTo($permission), "Administrador is missing the official permission [{$permission}].");
        }
    }
}
