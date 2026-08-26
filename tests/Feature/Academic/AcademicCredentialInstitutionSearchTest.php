<?php

namespace Tests\Feature\Academic;

use App\Models\Permission;
use App\Models\Specialty;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Src\Academic\AcademicCredential\Domain\DegreeLevel;
use Src\Academic\Teacher\Presentation\Livewire\TeacherProfileComponent;
use Tests\TestCase;

/**
 * OpenAlex institution search is enrichment-only: it must never become a
 * hard dependency for saving an academic credential, and it must never
 * influence affinity matching. Http::fake() only — never live Internet.
 */
class AcademicCredentialInstitutionSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_query_shorter_than_three_characters_does_not_call_the_provider(): void
    {
        // Http::fake() with no stub still records every call and answers
        // any unstubbed URL with a 200 — unlike bare preventStrayRequests(),
        // this makes an accidental provider call visible via assertNothingSent()
        // instead of silently degrading through the failure-handling path.
        Http::fake();

        $user = $this->userWithAcademicCredentialPermissions();
        $teacher = Teacher::factory()->create();
        $this->actingAs($user);

        Livewire::test(TeacherProfileComponent::class, ['teacher' => $teacher])
            ->call('openCreateModal')
            ->set('form.institution', 'UT')
            ->assertSet('institutionSuggestions', []);

        Http::assertNothingSent();
    }

    public function test_suggestions_are_rendered_when_the_provider_returns_results(): void
    {
        Http::fake([
            'api.openalex.org/*' => Http::response(['results' => [
                [
                    'id' => 'https://openalex.org/I1',
                    'display_name' => 'Universidad Técnica Nacional',
                    'hint' => 'Costa Rica',
                    'external_id' => 'https://ror.org/03e0y2b16',
                ],
            ]]),
        ]);

        $user = $this->userWithAcademicCredentialPermissions();
        $teacher = Teacher::factory()->create();
        $this->actingAs($user);

        Livewire::test(TeacherProfileComponent::class, ['teacher' => $teacher])
            ->call('openCreateModal')
            ->set('form.institution', 'Universidad Técnica')
            ->assertSet('institutionSuggestions.0.name', 'Universidad Técnica Nacional')
            ->assertSet('institutionSearchUnavailable', false);
    }

    public function test_selecting_a_suggestion_populates_the_institution_field(): void
    {
        $user = $this->userWithAcademicCredentialPermissions();
        $teacher = Teacher::factory()->create();
        $this->actingAs($user);

        Livewire::test(TeacherProfileComponent::class, ['teacher' => $teacher])
            ->call('openCreateModal')
            ->call('selectInstitution', 'Universidad Técnica Nacional')
            ->assertSet('form.institution', 'Universidad Técnica Nacional')
            ->assertSet('institutionSuggestions', []);
    }

    public function test_manually_typed_institution_still_saves_successfully(): void
    {
        Http::fake([
            'api.openalex.org/*' => Http::response(['results' => []]),
        ]);

        $user = $this->userWithAcademicCredentialPermissions();
        $teacher = Teacher::factory()->create();
        $specialty = Specialty::factory()->create();
        $this->actingAs($user);

        Livewire::test(TeacherProfileComponent::class, ['teacher' => $teacher])
            ->set('form.specialtyName', $specialty->name)
            ->set('form.degreeLevel', DegreeLevel::Licentiate->value)
            ->set('form.institution', 'Instituto Nacional de Aprendizaje')
            ->set('form.startDate', '2015-03-01')
            ->set('form.endDate', '2020-11-30')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('atestados', [
            'docente_id' => $teacher->id,
            'institucion' => 'Instituto Nacional de Aprendizaje',
        ]);
    }

    public function test_a_provider_outage_does_not_block_credential_creation(): void
    {
        Http::fake([
            'api.openalex.org/*' => Http::response('Internal Server Error', 500),
        ]);

        $user = $this->userWithAcademicCredentialPermissions();
        $teacher = Teacher::factory()->create();
        $specialty = Specialty::factory()->create();
        $this->actingAs($user);

        $component = Livewire::test(TeacherProfileComponent::class, ['teacher' => $teacher])
            ->set('form.specialtyName', $specialty->name)
            ->set('form.degreeLevel', DegreeLevel::Licentiate->value)
            ->set('form.institution', 'Universidad Técnica Nacional')
            ->assertSet('institutionSearchUnavailable', true)
            ->assertSet('institutionSuggestions', []);

        $component->set('form.startDate', '2015-03-01')
            ->set('form.endDate', '2020-11-30')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('atestados', [
            'docente_id' => $teacher->id,
            'institucion' => 'Universidad Técnica Nacional',
        ]);
    }

    public function test_an_institution_not_among_the_suggestions_is_still_valid(): void
    {
        Http::fake([
            'api.openalex.org/*' => Http::response(['results' => [
                ['id' => 'https://openalex.org/I1', 'display_name' => 'Universidad Nacional'],
            ]]),
        ]);

        $user = $this->userWithAcademicCredentialPermissions();
        $teacher = Teacher::factory()->create();
        $specialty = Specialty::factory()->create();
        $this->actingAs($user);

        Livewire::test(TeacherProfileComponent::class, ['teacher' => $teacher])
            ->set('form.specialtyName', $specialty->name)
            ->set('form.degreeLevel', DegreeLevel::Licentiate->value)
            ->set('form.institution', 'Universidad Nacional')
            ->assertSet('institutionSuggestions.0.name', 'Universidad Nacional')
            ->set('form.institution', 'A Different Institute Entirely')
            ->set('form.startDate', '2015-03-01')
            ->set('form.endDate', '2020-11-30')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('atestados', [
            'docente_id' => $teacher->id,
            'institucion' => 'A Different Institute Entirely',
        ]);
    }

    public function test_an_institution_made_only_of_digits_is_rejected(): void
    {
        Http::fake();

        $user = $this->userWithAcademicCredentialPermissions();
        $teacher = Teacher::factory()->create();
        $specialty = Specialty::factory()->create();
        $this->actingAs($user);

        Livewire::test(TeacherProfileComponent::class, ['teacher' => $teacher])
            ->call('openCreateModal')
            ->set('form.specialtyName', $specialty->name)
            ->set('form.degreeLevel', DegreeLevel::Bachelor->value)
            ->set('form.institution', '123456')
            ->set('form.startDate', '2015-03-01')
            ->set('form.endDate', '2020-11-30')
            ->call('save')
            ->assertHasErrors(['form.institution' => 'regex']);

        $this->assertDatabaseMissing('atestados', ['institucion' => '123456']);
    }

    public function test_an_institution_with_letters_and_digits_is_accepted(): void
    {
        Http::fake();

        $user = $this->userWithAcademicCredentialPermissions();
        $teacher = Teacher::factory()->create();
        $specialty = Specialty::factory()->create();
        $this->actingAs($user);

        Livewire::test(TeacherProfileComponent::class, ['teacher' => $teacher])
            ->call('openCreateModal')
            ->set('form.specialtyName', $specialty->name)
            ->set('form.degreeLevel', DegreeLevel::Bachelor->value)
            ->set('form.institution', 'Sede Regional 2')
            ->set('form.startDate', '2015-03-01')
            ->set('form.endDate', '2020-11-30')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('atestados', ['institucion' => 'Sede Regional 2']);
    }

    private function userWithAcademicCredentialPermissions(): User
    {
        $user = User::factory()->create();

        $permission = Permission::query()->firstOrCreate(
            ['name' => 'atestados.gestionar'],
            ['module' => 'atestados', 'action' => 'gestionar'],
        );
        $user->givePermissionTo($permission->name);

        return $user;
    }
}
