<?php

namespace Tests\Feature\Academic;

use App\Models\AcademicCredential;
use App\Models\Specialty;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            'teacher_id' => $teacher->id,
            'specialty_id' => $specialty->id,
            'institution' => 'University of Costa Rica',
        ]);

        $this->actingAs(User::factory()->create());

        $response = $this->get(route('academic.teacher.profile', $teacher));

        $response->assertOk()
            ->assertSee('Ana Rojas')
            ->assertSee('Food Engineering')
            ->assertSee('University of Costa Rica')
            ->assertDontSee(__('New academic credential'));
    }
}
