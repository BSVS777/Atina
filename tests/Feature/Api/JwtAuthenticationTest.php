<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JwtAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_credentials_return_a_jwt(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertOk()->assertJsonStructure(['access_token', 'token_type', 'expires_in']);
        $this->assertSame('Bearer', $response->json('token_type'));
    }

    public function test_invalid_credentials_are_rejected(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401);
    }

    public function test_unknown_email_is_rejected(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'nobody@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(401);
    }

    public function test_me_requires_a_token(): void
    {
        $this->getJson('/api/me')->assertStatus(401);
    }

    public function test_me_rejects_a_malformed_token(): void
    {
        $this->withToken('not-a-jwt')->getJson('/api/me')->assertStatus(401);
    }

    public function test_me_rejects_a_token_with_an_invalid_signature(): void
    {
        $user = User::factory()->create();

        $forgedToken = JWT::encode([
            'iss' => config('jwt.issuer'),
            'iat' => time(),
            'exp' => time() + 3600,
            'sub' => $user->id,
        ], 'a-completely-different-secret-key', 'HS256');

        $this->withToken($forgedToken)->getJson('/api/me')->assertStatus(401);
    }

    public function test_me_rejects_an_expired_token(): void
    {
        $user = User::factory()->create();

        $expiredToken = JWT::encode([
            'iss' => config('jwt.issuer'),
            'iat' => time() - 7200,
            'exp' => time() - 3600,
            'sub' => $user->id,
        ], (string) config('jwt.secret'), 'HS256');

        $this->withToken($expiredToken)->getJson('/api/me')->assertStatus(401);
    }

    public function test_a_valid_token_accesses_me(): void
    {
        $user = User::factory()->create(['name' => 'Ana Pérez']);

        $token = $this->loginAndGetToken($user);

        $response = $this->withToken($token)->getJson('/api/me');

        $response->assertOk()->assertJson([
            'id' => $user->id,
            'name' => 'Ana Pérez',
            'email' => $user->email,
        ]);
    }

    public function test_roles_and_permissions_are_retained_through_jwt(): void
    {
        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('Docente');

        $token = $this->loginAndGetToken($user);

        $response = $this->withToken($token)->getJson('/api/me');

        $response->assertOk()
            ->assertJsonFragment(['roles' => ['Docente']])
            ->assertJsonPath('permissions', fn (array $permissions) => in_array('oferta.consultar', $permissions, true)
                && in_array('archivos.descargar', $permissions, true));
    }

    public function test_administrator_role_works_through_jwt(): void
    {
        $this->seed(PermissionSeeder::class);
        $this->seed(RoleSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('Administrador');

        $token = $this->loginAndGetToken($user);

        $response = $this->withToken($token)->getJson('/api/me');

        $response->assertOk()->assertJsonFragment(['roles' => ['Administrador']]);
        $this->assertTrue(
            in_array('usuarios.gestionar', $response->json('permissions'), true),
        );
    }

    private function loginAndGetToken(User $user): string
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        return $response->json('access_token');
    }
}
