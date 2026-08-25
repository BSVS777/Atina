<?php

namespace Tests\Unit\Academic\Fakes;

use Src\Academic\AcademicCredential\Domain\Contracts\InstitutionSearchServiceInterface;
use Src\Academic\AcademicCredential\Domain\InstitutionSearchResult;

final class FakeInstitutionSearchService implements InstitutionSearchServiceInterface
{
    /** @var list<InstitutionSearchResult> */
    private array $results;

    public ?string $lastQuery = null;

    public ?int $lastLimit = null;

    public bool $wasCalled = false;

    /**
     * @param  list<InstitutionSearchResult>  $results
     */
    public function __construct(array $results = [])
    {
        $this->results = $results;
    }

    public function search(string $query, int $limit): array
    {
        $this->wasCalled = true;
        $this->lastQuery = $query;
        $this->lastLimit = $limit;

        return $this->results;
    }
}
