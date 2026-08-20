<?php

declare(strict_types=1);

namespace Tests\Feature\Academic\Fakes;

use Src\Shared\Export\Contracts\ExcelExporterInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Captures the exact rows a component hands to the export pipeline
 * instead of writing a real xlsx via OpenSpout — lets a test assert on
 * filtered row content/count directly rather than parsing a binary
 * spreadsheet.
 */
final class CapturingExcelExporter implements ExcelExporterInterface
{
    /** @var array<int, array<string, mixed>> */
    public array $rows = [];

    public function streamDownload(iterable $rows, string $filename): StreamedResponse
    {
        foreach ($rows as $row) {
            $this->rows[] = $row;
        }

        return response()->streamDownload(function (): void {}, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
