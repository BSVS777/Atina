<?php

namespace Tests\Feature\Academic;

use App\Models\AcademicTerm;
use App\Models\CourseGroup;
use App\Models\Permission;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Src\Academic\AffinityCatalog\Domain\Entities\AffinityCatalogVersion;
use Src\Academic\TeacherAssignment\Domain\Entities\TeacherAssignment;
use Src\Academic\TeacherAssignment\Domain\Entities\TechnicalNote;
use Src\Academic\TeacherAssignment\Presentation\Livewire\TeacherAssignmentComponent;
use Tests\TestCase;

/**
 * Official permission matrix (Docs/DIARIO_DECISIONES_IA.md): catalog
 * management is Administrador-only (`catalogo.gestionar`); proposing
 * assignments and no-catalog decisions are `atinencia.verificar`
 * (Administrador + Coordinadora de Docencia); ratifying/rejecting a
 * Technical Note is the stricter `nota_tecnica.aprobar`
 * (Administrador only).
 */
class TeacherAssignmentAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_without_permission_cannot_open_the_propose_modal(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(TeacherAssignmentComponent::class)
            ->call('openProposeModal')
            ->assertForbidden();
    }

    public function test_a_user_with_atinencia_verificar_can_propose_an_assignment(): void
    {
        $user = $this->userWithPermission('atinencia.verificar');
        $teacher = Teacher::factory()->create();
        $term = AcademicTerm::factory()->create();
        $group = CourseGroup::factory()->create(['academic_term_id' => $term->id]);
        $this->actingAs($user);

        Livewire::test(TeacherAssignmentComponent::class)
            ->set('proposeForm.teacherId', $teacher->id)
            ->set('proposeForm.courseGroupId', $group->id)
            ->call('propose')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('asignaciones_docentes', [
            'docente_id' => $teacher->id,
            'grupo_id' => $group->id,
        ]);
    }

    public function test_ratifying_a_technical_note_requires_nota_tecnica_aprobar_not_atinencia_verificar(): void
    {
        $user = $this->userWithPermission('atinencia.verificar');
        $this->actingAs($user);

        $this->assertFalse($user->can('approve', TechnicalNote::class));
    }

    public function test_administrador_permission_can_ratify_a_technical_note(): void
    {
        $user = $this->userWithPermission('nota_tecnica.aprobar');
        $this->actingAs($user);

        $this->assertTrue($user->can('approve', TechnicalNote::class));
    }

    public function test_creating_a_catalog_version_requires_catalogo_gestionar(): void
    {
        $withoutPermission = $this->userWithPermission('atinencia.verificar');
        $this->assertFalse($withoutPermission->can('create', AffinityCatalogVersion::class));

        $withPermission = $this->userWithPermission('catalogo.gestionar');
        $this->assertTrue($withPermission->can('create', AffinityCatalogVersion::class));
    }

    public function test_deciding_a_no_catalog_assignment_requires_atinencia_verificar(): void
    {
        $withoutPermission = User::factory()->create();
        $this->assertFalse($withoutPermission->can('decide', TeacherAssignment::class));

        $withPermission = $this->userWithPermission('atinencia.verificar');
        $this->assertTrue($withPermission->can('decide', TeacherAssignment::class));
    }

    private function userWithPermission(string $name): User
    {
        $user = User::factory()->create();
        [$module, $action] = explode('.', $name, 2);

        $permission = Permission::query()->firstOrCreate(['name' => $name], ['module' => $module, 'action' => $action]);
        $user->givePermissionTo($permission->name);

        return $user;
    }
}
