<?php

declare(strict_types=1);

namespace Src\IdentityAccess\Authentication\Domain\Exceptions;

final class ExpiredTokenException extends TokenException
{
    public static function make(): self
    {
        return new self('The provided token has expired.');
    }
}
