<?php

declare(strict_types=1);

namespace Src\Academic\TeacherAssignment\Domain\Contracts;

/**
 * File metadata for the Technical Note's signed PDF, captured at upload
 * time in Presentation and handed to the repository so it can create the
 * professor-provided `archivos` row (DO-02b's mandatory attachment).
 */
final class UploadedDocument
{
    public function __construct(
        public readonly string $storagePath,
        public readonly string $originalFileName,
        public readonly string $mimeType,
        public readonly int $sizeBytes,
        public readonly string $hashSha256,
    ) {}
}
