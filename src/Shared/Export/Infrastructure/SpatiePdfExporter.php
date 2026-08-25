<?php

declare(strict_types=1);

namespace Src\Shared\Export\Infrastructure;

use Spatie\LaravelPdf\Facades\Pdf;
use Src\Shared\Export\Contracts\PdfExporterInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Renders HTML to PDF via spatie/laravel-pdf's DOMPDF driver — pure PHP,
 * in-process rendering. Every report this app exports through this class
 * (`resources/views/exports/table-pdf.blade.php`) is static server-side
 * content (title, table headers, rows, dates) with no JavaScript, Alpine,
 * Livewire, or browser-only CSS, so there is no rendering requirement a
 * headless browser would satisfy that DOMPDF doesn't already — see the
 * `driver` key in `config/laravel-pdf.php` for the full reasoning. That
 * removes an entire process boundary (no Node/Puppeteer/Chrome child
 * process to spawn, time out, or fail to find on a given machine) from
 * every PDF download.
 *
 * Uses ->generatePdfContent() (raw bytes) rather than the package's own
 * ->name()/Responsable path deliberately: that path has a documented,
 * open issue specifically inside Livewire ("Livewire needs pdf as a
 * string not base64" — spatie/laravel-pdf discussion #120), where the
 * PDF arrives base64-encoded instead of as a clean binary stream.
 * Generating the bytes ourselves and handing them to Laravel's own
 * response()->streamDownload() sidesteps that entirely, and keeps this
 * adapter symmetric with SpatieExcelExporter.
 */
final class SpatiePdfExporter implements PdfExporterInterface
{
    public function fromHtml(string $html, string $filename, string $paperSize = 'a4'): StreamedResponse
    {
        $pdfBytes = Pdf::html($html)
            ->format($paperSize)
            ->generatePdfContent();

        // We already have the complete PDF in memory at this point (unlike
        // the Excel export, which genuinely streams row-by-row without ever
        // knowing the total size upfront) — so, unlike Excel, we CAN tell
        // the browser the exact byte count via Content-Length. That gets
        // you an accurate download progress bar instead of an "unknown
        // size" spinner; free correctness, not a performance trick.
        return response()->streamDownload(function () use ($pdfBytes): void {
            echo $pdfBytes;
        }, $filename, [
            'Content-Type' => 'application/pdf',
            'Content-Length' => (string) strlen($pdfBytes),
        ]);
    }
}
