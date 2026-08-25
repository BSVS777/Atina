<?php

declare(strict_types=1);

namespace Src\IdentityAccess\Authentication\Domain\Exceptions;

final class InvalidCredentialsException extends \RuntimeException
{
    public static function make(): self
    {
        return new self('These credentials do not match our records.');
    }
}
