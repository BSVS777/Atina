<?php

namespace Tests\Unit\Academic;

use Src\Academic\AcademicCredential\Application\UseCases\SearchAcademicInstitutionsUseCase;
use Src\Academic\AcademicCredential\Domain\InstitutionSearchResult;
use Tests\TestCase;
use Tests\Unit\Academic\Fakes\FakeInstitutionSearchService;

class SearchAcademicInstitutionsUseCaseTest extends TestCase
{
    public function test_a_query_shorter_than_the_minimum_length_never_reaches_the_port(): void
    {
        $port = new FakeInstitutionSearchService;
        $useCase = new SearchAcademicInstitutionsUseCase($port);

        $result = $useCase->handle('UT', 8);

        $this->assertSame([], $result);
        $this->assertFalse($port->wasCalled);
    }

    public function test_whitespace_only_input_never_reaches_the_port(): void
    {
        $port = new FakeInstitutionSearchService;
        $useCase = new SearchAcademicInstitutionsUseCase($port);

        $result = $useCase->handle('    ', 8);

        $this->assertSame([], $result);
        $this->assertFalse($port->wasCalled);
    }

    public function test_a_meaningful_query_is_normalized_and_delegated_to_the_port(): void
    {
        $expected = [new InstitutionSearchResult(
            externalId: 'https://openalex.org/I1',
            name: 'Universidad Técnica Nacional',
            hint: 'Costa Rica',
            countryCode: null,
            rorId: 'https://ror.org/abc123',
        )];
        $port = new FakeInstitutionSearchService($expected);
        $useCase = new SearchAcademicInstitutionsUseCase($port);

        $result = $useCase->handle('  Universidad   Técnica  ', 8);

        $this->assertSame($expected, $result);
        $this->assertSame('Universidad Técnica', $port->lastQuery);
    }

    public function test_the_result_limit_is_bounded_to_the_configured_maximum(): void
    {
        $port = new FakeInstitutionSearchService;
        $useCase = new SearchAcademicInstitutionsUseCase($port);

        $useCase->handle('Universidad Técnica', 500);

        $this->assertSame(SearchAcademicInstitutionsUseCase::MAX_LIMIT, $port->lastLimit);
    }

    public function test_the_result_limit_is_never_lower_than_one(): void
    {
        $port = new FakeInstitutionSearchService;
        $useCase = new SearchAcademicInstitutionsUseCase($port);

        $useCase->handle('Universidad Técnica', 0);

        $this->assertSame(1, $port->lastLimit);
    }
}
