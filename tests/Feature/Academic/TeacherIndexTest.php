<?php

namespace Tests\Feature\Academic;

use App\Models\Teacher;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $response = $this->get(route('academic.teacher.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_see_the_teacher_list(): void
    {
        Teacher::factory()->create(['first_name' => 'Carlos', 'last_name' => 'Mendez']);
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('academic.teacher.index'));

        $response->assertOk()->assertSee('Carlos Mendez');
    }
}
