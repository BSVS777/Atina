<?php

namespace Tests\Unit\IdentityAccess\Fakes;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Minimal identity for token-issuance tests — no Eloquent, no database.
 */
final class FakeAuthenticatable implements Authenticatable
{
    public function __construct(private readonly int $id) {}

    public function getAuthIdentifierName(): string
    {
        return 'id';
    }

    public function getAuthIdentifier(): int
    {
        return $this->id;
    }

    public function getAuthPasswordName(): string
    {
        return 'password';
    }

    public function getAuthPassword(): string
    {
        return 'irrelevant-for-token-issuance';
    }

    public function getRememberToken(): string
    {
        return '';
    }

    public function setRememberToken($value): void {}

    public function getRememberTokenName(): string
    {
        return 'remember_token';
    }
}
