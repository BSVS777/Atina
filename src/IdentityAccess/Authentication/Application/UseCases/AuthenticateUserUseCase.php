<?php

declare(strict_types=1);

namespace Src\IdentityAccess\Authentication\Application\UseCases;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Src\IdentityAccess\Authentication\Domain\Contracts\TokenServiceInterface;
use Src\IdentityAccess\Authentication\Domain\Exceptions\InvalidCredentialsException;
use Src\IdentityAccess\Authentication\Domain\ValueObjects\IssuedToken;

/**
 * Credential check reuses the same users table/model and password hashes
 * as Fortify — this is a second entry point onto the same identity, not a
 * parallel auth system.
 */
final class AuthenticateUserUseCase
{
    public function __construct(
        private readonly TokenServiceInterface $tokens,
    ) {}

    public function handle(string $email, string $password): IssuedToken
    {
        $user = User::query()->where('email', $email)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            throw InvalidCredentialsException::make();
        }

        return $this->tokens->issue($user);
    }
}
