<?php

declare(strict_types=1);

namespace Src\IdentityAccess\Authentication\Domain\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use Src\IdentityAccess\Authentication\Domain\Exceptions\ExpiredTokenException;
use Src\IdentityAccess\Authentication\Domain\Exceptions\InvalidTokenException;
use Src\IdentityAccess\Authentication\Domain\ValueObjects\IssuedToken;

/**
 * Token issuance/verification contract. Implementations own the concrete
 * signing scheme (JWT or otherwise) — nothing outside Infrastructure may
 * reference a specific token library.
 */
interface TokenServiceInterface
{
    public function issue(Authenticatable $user): IssuedToken;

    /**
     * @throws ExpiredTokenException
     * @throws InvalidTokenException
     */
    public function resolveSubject(string $token): int;
}
