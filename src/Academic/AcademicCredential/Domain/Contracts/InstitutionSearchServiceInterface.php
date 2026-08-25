<?php

declare(strict_types=1);

namespace Src\Academic\AcademicCredential\Domain\Contracts;

use Src\Academic\AcademicCredential\Domain\InstitutionSearchResult;

/**
 * Provider-neutral academic institution lookup, used only to assist
 * filling in a credential's Institution field — never to validate or
 * authorize it. Implementations live in Infrastructure and must translate
 * every provider-specific failure into
 * Exceptions\InstitutionSearchUnavailableException before it crosses this
 * boundary.
 */
interface InstitutionSearchServiceInterface
{
    /**
     * @return list<InstitutionSearchResult>
     */
    public function search(string $query, int $limit): array;
}
