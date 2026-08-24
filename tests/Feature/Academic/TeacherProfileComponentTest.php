<?php

namespace Tests\Feature\Academic;

use App\Models\AcademicCredential;
use App\Models\Career;
use App\Models\Course;
use App\Models\Specialty;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Src\Academic\AffinityCatalog\Application\DTOs\AffinityCatalogVersionDTO;
use Src\Academic\AffinityCatalog\Application\UseCases\CreateAffinityCatalogVersionUseCase;
use Src\Academic\Teacher\Presentation\Livewire\TeacherProfileComponent;
use Tests\TestCase;

class TeacherProfileComponentTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $teacher = Teacher::factory()->create();

        $response = $this->get(route('academic.teacher.profile', $teacher));

        $response->assertRedirect(route('login'));
    }

    public function test_shows_the_teacher_and_their_existing_credentials_read_only(): void
    {
        $teacher = Teacher::factory()->create(['first_name' => 'Ana', 'last_name' => 'Rojas']);
        $specialty = Specialty::factory()->create(['name' => 'Food Engineering']);
        AcademicCredential::factory()->create([
            'docente_id' => $teacher->id,
            'especialidad_id' => $specialty->id,
            'institucion' => 'University of Costa Rica',
        ]);

        $this->actingAs(User::factory()->create());

        $response = $this->get(route('academic.teacher.profile', $teacher));

        $response->assertOk()
            ->assertSee('Ana Rojas')
            ->assertSee('Food Engineering')
            ->assertSee('University of Costa Rica')
            ->assertDontSee(__('New academic credential'));
    }

    public function test_evaluating_affinity_in_a_course_context_shows_career_course_version_and_agreement(): void
    {
        $career = Career::factory()->create(['name' => 'Ingeniería del Software']);
        $course = Course::factory()->create([
            'career_id' => $career->id,
            'code' => 'ISW-521',
            'name' => 'Programación en Ambiente Web I',
        ]);
        $specialty = Specialty::factory()->create();

        app(CreateAffinityCatalogVersionUseCase::class)->handle(new AffinityCatalogVersionDTO(
            courseId: $course->id,
            councilAgreement: 'Acuerdo XX-2026',
            gazetteNumber: 'YY',
            effectiveStartDate: '2026-01-01',
            effectiveEndDate: null,
            specialtyIds: [$specialty->id],
        ), null);

        $teacher = Teacher::factory()->create();
        $this->actingAs(User::factory()->create());

        Livewire::test(TeacherProfileComponent::class, ['teacher' => $teacher])
            ->set('contextCourseId', $course->id)
            ->assertSee('Ingeniería del Software')
            ->assertSee('ISW-521')
            ->assertSee('Programación en Ambiente Web I')
            ->assertSee('Acuerdo XX-2026')
            ->assertSee('YY');
    }
}
