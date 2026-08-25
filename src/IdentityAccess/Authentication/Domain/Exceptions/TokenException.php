<?php

declare(strict_types=1);

namespace Src\IdentityAccess\Authentication\Domain\Exceptions;

/**
 * Base type for token-resolution failures. The JWT middleware catches this
 * (never the library-specific exceptions) so callers never depend on the
 * underlying JWT implementation.
 */
abstract class TokenException extends \RuntimeException {}
