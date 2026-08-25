<?php

declare(strict_types=1);

namespace Src\Academic\AcademicCredential\Application\UseCases;

use Src\Academic\AcademicCredential\Domain\Contracts\InstitutionSearchServiceInterface;
use Src\Academic\AcademicCredential\Domain\InstitutionSearchResult;

/**
 * Thin normalization layer over InstitutionSearchServiceInterface: trims
 * whitespace, enforces a minimum query length so trivial input never
 * reaches the provider, and bounds the result limit. Contains no
 * HTTP-specific code and never participates in affinity decisions.
 * Provider failures propagate as
 * Domain\Exceptions\InstitutionSearchUnavailableException — deciding what
 * to show the user is the caller's responsibility.
 */
final class SearchAcademicInstitutionsUseCase
{
    public const MIN_QUERY_LENGTH = 3;

    public const MAX_LIMIT = 20;

    public function __construct(
        private readonly InstitutionSearchServiceInterface $institutionSearch,
    ) {}

    /**
     * @return list<InstitutionSearchResult>
     */
    public function handle(string $query, int $limit = 8): array
    {
        $normalized = trim(preg_replace('/\s+/', ' ', $query) ?? $query);

        if (mb_strlen($normalized) < self::MIN_QUERY_LENGTH) {
            return [];
        }

        $boundedLimit = max(1, min($limit, self::MAX_LIMIT));

        return $this->institutionSearch->search($normalized, $boundedLimit);
    }
}
