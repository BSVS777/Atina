<?php

declare(strict_types=1);

namespace Src\IdentityAccess\Authentication\Domain\ValueObjects;

final class IssuedToken
{
    public function __construct(
        public readonly string $accessToken,
        public readonly string $tokenType,
        public readonly int $expiresIn,
    ) {}
}
