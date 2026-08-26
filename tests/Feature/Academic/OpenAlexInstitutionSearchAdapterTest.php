<?php

namespace Tests\Feature\Academic;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Src\Academic\AcademicCredential\Domain\Exceptions\InstitutionSearchUnavailableException;
use Src\Academic\AcademicCredential\Domain\InstitutionSearchResult;
use Src\Academic\AcademicCredential\Infrastructure\Services\OpenAlexInstitutionSearchService;
use Tests\TestCase;

/**
 * Http::fake() only — never depends on live Internet access. RefreshDatabase
 * is only needed for the database-cache-store regression test below (the
 * `cache` table).
 */
class OpenAlexInstitutionSearchAdapterTest extends TestCase
{
    use RefreshDatabase;

    private function service(): OpenAlexInstitutionSearchService
    {
        return new OpenAlexInstitutionSearchService(
            baseUrl: 'https://api.openalex.org',
            apiKey: null,
            timeoutSeconds: 5,
            cacheTtlSeconds: 900,
        );
    }

    public function test_it_calls_the_openalex_autocomplete_endpoint_with_the_encoded_query(): void
    {
        Http::fake([
            'api.openalex.org/*' => Http::response(['results' => []]),
        ]);

        $this->service()->search('Universidad Técnica Nacional', 8);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/autocomplete/institutions')
                && $request['q'] === 'Universidad Técnica Nacional';
        });
    }

    public function test_it_maps_a_successful_response_into_provider_neutral_results_and_respects_the_limit(): void
    {
        Http::fake([
            'api.openalex.org/*' => Http::response(['results' => [
                [
                    'id' => 'https://openalex.org/I1',
                    'display_name' => 'Universidad Técnica Nacional',
                    'hint' => 'Costa Rica',
                    'external_id' => 'https://ror.org/03e0y2b16',
                    'entity_type' => 'institution',
                    'works_count' => 1000,
                ],
                [
                    'id' => 'https://openalex.org/I2',
                    'display_name' => 'Universidad Nacional',
                    'hint' => 'Costa Rica',
                    'external_id' => 'https://ror.org/046ffvj20',
                ],
                [
                    'id' => 'https://openalex.org/I3',
                    'display_name' => 'Universidad de Costa Rica',
                ],
            ]]),
        ]);

        $results = $this->service()->search('Universidad', 2);

        $this->assertCount(2, $results);
        $this->assertContainsOnlyInstancesOf(InstitutionSearchResult::class, $results);
        $this->assertSame('Universidad Técnica Nacional', $results[0]->name);
        $this->assertSame('Costa Rica', $results[0]->hint);
        $this->assertSame('https://openalex.org/I1', $results[0]->externalId);
        $this->assertSame('https://ror.org/03e0y2b16', $results[0]->rorId);

        // The raw payload (works_count, entity_type, ...) never leaks past the adapter.
        $this->assertSame(
            ['externalId', 'name', 'hint', 'countryCode', 'rorId'],
            array_keys(get_object_vars($results[0])),
        );
    }

    public function test_empty_results_are_not_treated_as_a_failure(): void
    {
        Http::fake([
            'api.openalex.org/*' => Http::response(['results' => []]),
        ]);

        $results = $this->service()->search('Zzzzzzzzz Nonexistent University', 8);

        $this->assertSame([], $results);
    }

    public function test_a_connection_failure_is_reported_as_unavailable(): void
    {
        Http::fake(function (): never {
            throw new ConnectionException('Connection timed out.');
        });

        $this->expectException(InstitutionSearchUnavailableException::class);

        $this->service()->search('Universidad Técnica Nacional', 8);
    }

    public function test_a_rate_limit_response_is_reported_as_unavailable(): void
    {
        Http::fake([
            'api.openalex.org/*' => Http::response(['error' => 'rate limited'], 429),
        ]);

        $this->expectException(InstitutionSearchUnavailableException::class);

        $this->service()->search('Universidad Técnica Nacional', 8);
    }

    public function test_a_server_error_response_is_reported_as_unavailable(): void
    {
        Http::fake([
            'api.openalex.org/*' => Http::response('Internal Server Error', 500),
        ]);

        $this->expectException(InstitutionSearchUnavailableException::class);

        $this->service()->search('Universidad Técnica Nacional', 8);
    }

    public function test_malformed_json_is_reported_as_unavailable(): void
    {
        Http::fake([
            'api.openalex.org/*' => Http::response('not valid json', 200, ['Content-Type' => 'text/plain']),
        ]);

        $this->expectException(InstitutionSearchUnavailableException::class);

        $this->service()->search('Universidad Técnica Nacional', 8);
    }

    public function test_an_unexpected_response_shape_is_reported_as_unavailable(): void
    {
        Http::fake([
            'api.openalex.org/*' => Http::response(['unexpected' => 'shape']),
        ]);

        $this->expectException(InstitutionSearchUnavailableException::class);

        $this->service()->search('Universidad Técnica Nacional', 8);
    }

    /**
     * Regression test: phpunit.xml runs with CACHE_STORE=array, whose
     * ArrayStore never actually serializes anything, so it can't catch a
     * cache that stores real objects. The app's config/cache.php sets
     * serializable_classes => false, so the database (and every other
     * serializing) store unserializes any cached object into
     * __PHP_Incomplete_Class on a cache hit unless the cached payload is
     * plain arrays/scalars. Switch to the database store here specifically
     * to exercise that real unserialize path.
     */
    public function test_a_cache_hit_against_a_serializing_store_still_returns_result_objects(): void
    {
        config(['cache.default' => 'database']);

        Http::fake([
            'api.openalex.org/*' => Http::response(['results' => [
                [
                    'id' => 'https://openalex.org/I1',
                    'display_name' => 'Universidad Técnica Nacional',
                    'hint' => 'Costa Rica',
                ],
            ]]),
        ]);

        $service = $this->service();

        $service->search('Universidad', 8);
        $second = $service->search('Universidad', 8);

        $this->assertContainsOnlyInstancesOf(InstitutionSearchResult::class, $second);
        $this->assertSame('Universidad Técnica Nacional', $second[0]->name);
        $this->assertSame('Costa Rica', $second[0]->hint);
    }
}
