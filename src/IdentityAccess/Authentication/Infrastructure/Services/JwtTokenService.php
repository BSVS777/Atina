<?php

declare(strict_types=1);

namespace Src\IdentityAccess\Authentication\Infrastructure\Services;

use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Contracts\Auth\Authenticatable;
use Src\IdentityAccess\Authentication\Domain\Contracts\TokenServiceInterface;
use Src\IdentityAccess\Authentication\Domain\Exceptions\ExpiredTokenException;
use Src\IdentityAccess\Authentication\Domain\Exceptions\InvalidTokenException;
use Src\IdentityAccess\Authentication\Domain\ValueObjects\IssuedToken;

/**
 * TokenServiceInterface backed by firebase/php-jwt (HS256). This is the
 * only class in the Authentication context allowed to reference the JWT
 * library — Domain and Application stay signing-scheme agnostic.
 */
final class JwtTokenService implements TokenServiceInterface
{
    private const ALGO = 'HS256';

    public function __construct(
        private readonly string $secret,
        private readonly int $ttlMinutes,
        private readonly string $issuer,
    ) {}

    public function issue(Authenticatable $user): IssuedToken
    {
        $issuedAt = time();
        $expiresIn = $this->ttlMinutes * 60;

        $token = JWT::encode([
            'iss' => $this->issuer,
            'iat' => $issuedAt,
            'exp' => $issuedAt + $expiresIn,
            'sub' => $user->getAuthIdentifier(),
        ], $this->secret, self::ALGO);

        return new IssuedToken(accessToken: $token, tokenType: 'Bearer', expiresIn: $expiresIn);
    }

    public function resolveSubject(string $token): int
    {
        try {
            $claims = JWT::decode($token, new Key($this->secret, self::ALGO));
        } catch (ExpiredException) {
            throw ExpiredTokenException::make();
        } catch (\Throwable) {
            throw InvalidTokenException::make();
        }

        if (! isset($claims->sub) || ! is_numeric($claims->sub)) {
            throw InvalidTokenException::make();
        }

        return (int) $claims->sub;
    }
}
