<?php

declare(strict_types=1);

namespace Tests\Feature\Academic\Fakes;

use Src\Shared\Export\Contracts\PdfExporterInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Captures the rendered HTML a component hands to the PDF pipeline
 * instead of invoking headless Chrome via Browsershot — the raw HTML has
 * plain, greppable text, letting a test assert filtered content directly
 * rather than parsing a compressed PDF binary.
 */
final class CapturingPdfExporter implements PdfExporterInterface
{
    public string $html = '';

    public function fromHtml(string $html, string $filename, string $paperSize = 'a4'): StreamedResponse
    {
        $this->html = $html;

        return response()->streamDownload(function (): void {}, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
