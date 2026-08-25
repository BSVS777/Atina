<?php

namespace Tests\Feature\IdentityAccess;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression coverage for the topbar profile menu, which used to read a
 * non-existent `role` scalar property and silently fall back to a
 * hardcoded "Academic Coordinator" label for every user regardless of
 * their real, seeded roles() relationship. See Docs/DIARIO_DECISIONES_IA.md.
 */
class TopbarRoleDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_role_is_displayed_in_the_profile_menu(): void
    {
        $this->seed(RoleSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('Superadmin');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Superadmin');
    }

    public function test_administrador_role_is_displayed_in_the_profile_menu(): void
    {
        $this->seed(RoleSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('Administrador');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Administrador');
    }

    public function test_coordinator_role_is_displayed_with_its_real_persisted_name(): void
    {
        $this->seed(RoleSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('Coordinadora de Docencia');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Coordinadora de Docencia');
    }

    public function test_user_with_no_assigned_role_sees_a_neutral_fallback(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(__('No role assigned'))
            ->assertDontSee('Academic Coordinator')
            ->assertDontSee('Coordinadora Académica');
    }

    public function test_no_hardcoded_academic_coordinator_fallback_remains(): void
    {
        $topbar = file_get_contents(resource_path('views/components/siga/topbar.blade.php'));

        $this->assertStringNotContainsString('Academic Coordinator', $topbar);
        $this->assertStringNotContainsString('->role ??', $topbar);
    }
}
