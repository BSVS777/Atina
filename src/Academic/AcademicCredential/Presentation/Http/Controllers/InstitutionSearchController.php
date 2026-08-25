<?php

declare(strict_types=1);

namespace Src\Academic\AcademicCredential\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\Academic\AcademicCredential\Application\UseCases\SearchAcademicInstitutionsUseCase;
use Src\Academic\AcademicCredential\Domain\Exceptions\InstitutionSearchUnavailableException;
use Src\Academic\AcademicCredential\Domain\InstitutionSearchResult;

/**
 * Read-only JWT-protected demonstration of the API boundary. Reuses
 * SearchAcademicInstitutionsUseCase — no logic is duplicated from the
 * Livewire credential form. A provider outage never surfaces as a 5xx:
 * it degrades to an empty result set, consistent with the rest of this
 * enrichment-only feature.
 */
final class InstitutionSearchController extends Controller
{
    public function __invoke(Request $request, SearchAcademicInstitutionsUseCase $useCase): JsonResponse
    {
        $data = $request->validate([
            'q' => ['required', 'string', 'min:'.SearchAcademicInstitutionsUseCase::MIN_QUERY_LENGTH, 'max:150'],
        ]);

        try {
            $results = $useCase->handle($data['q'], (int) config('openalex.institution_limit'));
        } catch (InstitutionSearchUnavailableException) {
            return response()->json([
                'results' => [],
                'message' => __('Institution suggestions are unavailable right now. You can still type the institution manually.'),
            ]);
        }

        return response()->json([
            'results' => array_map(fn (InstitutionSearchResult $result): array => [
                'externalId' => $result->externalId,
                'name' => $result->name,
                'hint' => $result->hint,
                'countryCode' => $result->countryCode,
                'rorId' => $result->rorId,
            ], $results),
        ]);
    }
}
