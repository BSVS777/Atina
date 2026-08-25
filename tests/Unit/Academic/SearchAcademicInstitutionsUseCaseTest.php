<?php

namespace Tests\Unit\Academic;

use PHPUnit\Framework\TestCase;
use Src\Academic\AcademicCredential\Application\UseCases\SearchAcademicInstitutionsUseCase;
use Src\Academic\AcademicCredential\Domain\InstitutionSearchResult;
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

    public function test_a_query_of_exactly_the_minimum_length_reaches_the_port(): void
    {
        $port = new FakeInstitutionSearchService;
        $useCase = new SearchAcademicInstitutionsUseCase($port);

        $useCase->handle(str_repeat('U', SearchAcademicInstitutionsUseCase::MIN_QUERY_LENGTH), 8);

        $this->assertTrue($port->wasCalled);
    }

    public function test_the_minimum_length_is_measured_after_normalization(): void
    {
        // "U  T" collapses to "U T" (3 characters) and is long enough;
        // padding alone must never buy a query its way to the provider.
        $port = new FakeInstitutionSearchService;
        $useCase = new SearchAcademicInstitutionsUseCase($port);

        $useCase->handle('  U     T  ', 8);

        $this->assertSame('U T', $port->lastQuery);

        $tooShort = new FakeInstitutionSearchService;

        (new SearchAcademicInstitutionsUseCase($tooShort))->handle('  U   ', 8);

        $this->assertFalse($tooShort->wasCalled);
    }

    public function test_an_empty_provider_response_is_returned_unchanged(): void
    {
        $port = new FakeInstitutionSearchService;

        $result = (new SearchAcademicInstitutionsUseCase($port))->handle('Universidad Técnica', 8);

        $this->assertSame([], $result);
        $this->assertTrue($port->wasCalled);
    }

    public function test_provider_results_are_passed_through_without_filtering_or_reordering(): void
    {
        $expected = [
            new InstitutionSearchResult('https://openalex.org/I1', 'Universidad Técnica Nacional', 'Costa Rica', 'CR', null),
            new InstitutionSearchResult(null, 'Universidad de Costa Rica', null, null, null),
        ];
        $port = new FakeInstitutionSearchService($expected);

        $result = (new SearchAcademicInstitutionsUseCase($port))->handle('Universidad', 8);

        $this->assertSame($expected, $result);
    }

    public function test_a_limit_inside_the_allowed_range_is_forwarded_untouched(): void
    {
        $port = new FakeInstitutionSearchService;

        (new SearchAcademicInstitutionsUseCase($port))->handle('Universidad', 5);

        $this->assertSame(5, $port->lastLimit);
    }
}
