<?php

declare(strict_types=1);

namespace Src\Academic\AcademicCredential\Domain;

/**
 * Provider-neutral institution suggestion. Never carries a raw provider
 * payload — Infrastructure adapters must map into this shape at the
 * boundary so Domain/Application stay unaware of any specific provider.
 */
final class InstitutionSearchResult
{
    public function __construct(
        public readonly ?string $externalId,
        public readonly string $name,
        public readonly ?string $hint,
        public readonly ?string $countryCode,
        public readonly ?string $rorId,
    ) {}
}
