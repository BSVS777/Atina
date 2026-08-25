<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Src\IdentityAccess\Authentication\Domain\Contracts\TokenServiceInterface;
use Tests\TestCase;

class InstitutionSearchApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_requires_a_jwt(): void
    {
        $this->getJson('/api/institutions/search?q=Universidad')->assertStatus(401);
    }

    public function test_an_invalid_jwt_is_rejected(): void
    {
        $this->withToken('not-a-jwt')
            ->getJson('/api/institutions/search?q=Universidad')
            ->assertStatus(401);
    }

    public function test_a_valid_jwt_and_query_returns_provider_neutral_results(): void
    {
        Http::fake([
            'api.openalex.org/*' => Http::response(['results' => [
                ['id' => 'https://openalex.org/I1', 'display_name' => 'Universidad Técnica Nacional', 'hint' => 'Costa Rica'],
            ]]),
        ]);

        $token = $this->issueTokenFor(User::factory()->create());

        $this->withToken($token)
            ->getJson('/api/institutions/search?q=Universidad')
            ->assertOk()
            ->assertJson(['results' => [
                ['externalId' => 'https://openalex.org/I1', 'name' => 'Universidad Técnica Nacional', 'hint' => 'Costa Rica', 'countryCode' => null, 'rorId' => null],
            ]]);
    }

    public function test_a_valid_jwt_with_an_invalid_query_returns_a_validation_error(): void
    {
        $token = $this->issueTokenFor(User::factory()->create());

        $this->withToken($token)
            ->getJson('/api/institutions/search?q=ab')
            ->assertStatus(422);
    }

    public function test_a_provider_outage_returns_a_controlled_empty_response_instead_of_a_server_error(): void
    {
        Http::fake([
            'api.openalex.org/*' => Http::response('Internal Server Error', 500),
        ]);

        $token = $this->issueTokenFor(User::factory()->create());

        $this->withToken($token)
            ->getJson('/api/institutions/search?q=Universidad')
            ->assertOk()
            ->assertJson(['results' => []]);
    }

    private function issueTokenFor(User $user): string
    {
        return app(TokenServiceInterface::class)->issue($user)->accessToken;
    }
}
