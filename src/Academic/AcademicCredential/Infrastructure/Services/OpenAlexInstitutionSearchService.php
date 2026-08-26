<?php

declare(strict_types=1);

namespace Src\Academic\AcademicCredential\Infrastructure\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Src\Academic\AcademicCredential\Domain\Contracts\InstitutionSearchServiceInterface;
use Src\Academic\AcademicCredential\Domain\Exceptions\InstitutionSearchUnavailableException;
use Src\Academic\AcademicCredential\Domain\InstitutionSearchResult;

/**
 * InstitutionSearchServiceInterface backed by the OpenAlex Institutions
 * autocomplete endpoint (https://api.openalex.org/autocomplete/institutions).
 * This is the only class in the AcademicCredential context allowed to
 * reference Laravel's HTTP client or OpenAlex's response shape — Domain
 * and Application stay provider-agnostic. Every failure mode (connection
 * error, timeout, 4xx/429/5xx, malformed JSON, unexpected structure) is
 * normalized into InstitutionSearchUnavailableException; a genuinely empty
 * result set is not a failure and is returned as an empty list.
 */
final class OpenAlexInstitutionSearchService implements InstitutionSearchServiceInterface
{
    private const ENDPOINT = '/autocomplete/institutions';

    public function __construct(
        private readonly string $baseUrl,
        private readonly ?string $apiKey,
        private readonly int $timeoutSeconds,
        private readonly int $cacheTtlSeconds,
    ) {}

    /**
     * @return list<InstitutionSearchResult>
     */
    public function search(string $query, int $limit): array
    {
        $cacheKey = 'institution-search:'.md5(mb_strtolower($query)).":{$limit}";

        // Cache plain arrays, not InstitutionSearchResult objects: the app's
        // default cache config (config/cache.php: serializable_classes =>
        // false) unserializes cached objects into __PHP_Incomplete_Class on
        // every cache hit, since no class is allowlisted for unserialize.
        $cached = Cache::remember(
            $cacheKey,
            $this->cacheTtlSeconds,
            fn (): array => array_map(fn (InstitutionSearchResult $result): array => (array) $result, $this->fetch($query, $limit)),
        );

        return array_map(fn (array $result): InstitutionSearchResult => new InstitutionSearchResult(...$result), $cached);
    }

    /**
     * @return list<InstitutionSearchResult>
     */
    private function fetch(string $query, int $limit): array
    {
        try {
            $response = Http::baseUrl($this->baseUrl)
                ->timeout($this->timeoutSeconds)
                ->acceptJson()
                ->get(self::ENDPOINT, array_filter([
                    'q' => $query,
                    'api_key' => $this->apiKey,
                ], fn (?string $value): bool => $value !== null && $value !== ''));
        } catch (\Throwable $e) {
            // Broad catch by design: this is an enrichment-only integration,
            // so any transport-level failure (connection error, timeout, DNS,
            // a test environment blocking stray HTTP calls) must degrade to
            // "provider unavailable" rather than ever reach the caller raw.
            Log::warning('OpenAlex institution search request failed.', ['reason' => $e->getMessage()]);

            throw InstitutionSearchUnavailableException::make();
        }

        if ($response->failed()) {
            Log::warning('OpenAlex institution search returned an error status.', ['status' => $response->status()]);

            throw InstitutionSearchUnavailableException::make();
        }

        $payload = $response->json();

        if (! is_array($payload) || ! isset($payload['results']) || ! is_array($payload['results'])) {
            Log::warning('OpenAlex institution search returned an unexpected payload shape.');

            throw InstitutionSearchUnavailableException::make();
        }

        return array_values(collect($payload['results'])
            ->take($limit)
            ->map(fn (mixed $result): ?InstitutionSearchResult => $this->mapResult($result))
            ->filter()
            ->all());
    }

    private function mapResult(mixed $result): ?InstitutionSearchResult
    {
        if (! is_array($result) || ! is_string($result['display_name'] ?? null) || $result['display_name'] === '') {
            return null;
        }

        return new InstitutionSearchResult(
            externalId: is_string($result['id'] ?? null) ? $result['id'] : null,
            name: $result['display_name'],
            hint: is_string($result['hint'] ?? null) ? $result['hint'] : null,
            countryCode: is_string($result['country_code'] ?? null) ? $result['country_code'] : null,
            rorId: is_string($result['external_id'] ?? null) ? $result['external_id'] : null,
        );
    }
}
