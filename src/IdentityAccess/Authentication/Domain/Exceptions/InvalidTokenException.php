<?php

declare(strict_types=1);

namespace Src\IdentityAccess\Authentication\Domain\Exceptions;

/**
 * Covers missing, malformed, and signature-invalid tokens alike — the
 * Presentation layer must respond identically for all of them so it never
 * leaks which specific check failed.
 */
final class InvalidTokenException extends TokenException
{
    public static function make(): self
    {
        return new self('The provided token is invalid.');
    }
}
