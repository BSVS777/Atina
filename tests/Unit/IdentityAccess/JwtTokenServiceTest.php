<?php

namespace Tests\Unit\IdentityAccess;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Src\IdentityAccess\Authentication\Domain\Exceptions\ExpiredTokenException;
use Src\IdentityAccess\Authentication\Domain\Exceptions\InvalidTokenException;
use Src\IdentityAccess\Authentication\Infrastructure\Services\JwtTokenService;
use Tests\Unit\IdentityAccess\Fakes\FakeAuthenticatable;

/**
 * The HS256 token contract, exercised without booting Laravel, without a
 * database and without the API routes — those stay covered by
 * tests/Feature/Api/JwtAuthenticationTest.php.
 *
 * Expiration is provoked with a negative TTL instead of a clock
 * abstraction: the service already derives `exp` from the injected TTL,
 * so a negative one produces an already-expired token deterministically
 * and no extra indirection has to be added to production code.
 */
class JwtTokenServiceTest extends TestCase
{
    private const SECRET = 'unit-test-secret-0123456789abcdef0123456789abcdef';

    private const ISSUER = 'Atina-Unit';

    public function test_issuing_a_token_reports_the_configured_lifetime_in_seconds(): void
    {
        $issued = $this->service(ttlMinutes: 45)->issue(new FakeAuthenticatable(7));

        $this->assertSame('Bearer', $issued->tokenType);
        $this->assertSame(2700, $issued->expiresIn);
        $this->assertNotSame('', $issued->accessToken);
    }

    public function test_an_issued_token_carries_the_issuer_subject_and_expiration_claims(): void
    {
        $issued = $this->service()->issue(new FakeAuthenticatable(7));

        $claims = JWT::decode($issued->accessToken, new Key(self::SECRET, 'HS256'));

        $this->assertSame(self::ISSUER, $claims->iss);
        $this->assertSame(7, (int) $claims->sub);
        $this->assertSame($claims->iat + 3600, $claims->exp);
    }

    public function test_a_token_issued_by_the_service_resolves_back_to_its_subject(): void
    {
        $service = $this->service();

        $issued = $service->issue(new FakeAuthenticatable(42));

        $this->assertSame(42, $service->resolveSubject($issued->accessToken));
    }

    public function test_a_token_signed_with_another_secret_is_invalid(): void
    {
        $foreign = new JwtTokenService('a-different-secret-fedcba9876543210fedcba98', 60, self::ISSUER);
        $issued = $foreign->issue(new FakeAuthenticatable(7));

        $this->expectException(InvalidTokenException::class);

        $this->service()->resolveSubject($issued->accessToken);
    }

    public function test_a_token_whose_payload_was_tampered_with_is_invalid(): void
    {
        $issued = $this->service()->issue(new FakeAuthenticatable(7));
        [$header, $payload, $signature] = explode('.', $issued->accessToken);
        $forgedPayload = $this->encodeSegment(['iss' => self::ISSUER, 'iat' => time(), 'exp' => time() + 3600, 'sub' => 999]);

        $this->expectException(InvalidTokenException::class);

        $this->service()->resolveSubject("{$header}.{$forgedPayload}.{$signature}");
    }

    #[DataProvider('malformedTokens')]
    public function test_a_malformed_token_is_invalid(string $token): void
    {
        $this->expectException(InvalidTokenException::class);

        $this->service()->resolveSubject($token);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function malformedTokens(): array
    {
        return [
            'empty string' => [''],
            'not a jwt' => ['definitely-not-a-token'],
            'too few segments' => ['header.payload'],
            'garbage segments' => ['aaa.bbb.ccc'],
        ];
    }

    public function test_an_expired_token_is_reported_as_expired_not_merely_invalid(): void
    {
        // The Presentation layer distinguishes the two so it can tell the
        // client to re-authenticate rather than reject the credential.
        $expiredService = $this->service(ttlMinutes: -10);
        $issued = $expiredService->issue(new FakeAuthenticatable(7));

        $this->expectException(ExpiredTokenException::class);

        $this->service()->resolveSubject($issued->accessToken);
    }

    public function test_a_token_without_a_subject_claim_is_invalid(): void
    {
        $token = JWT::encode(['iss' => self::ISSUER, 'iat' => time(), 'exp' => time() + 3600], self::SECRET, 'HS256');

        $this->expectException(InvalidTokenException::class);

        $this->service()->resolveSubject($token);
    }

    public function test_a_token_with_a_non_numeric_subject_is_invalid(): void
    {
        $token = JWT::encode([
            'iss' => self::ISSUER,
            'iat' => time(),
            'exp' => time() + 3600,
            'sub' => 'admin@example.test',
        ], self::SECRET, 'HS256');

        $this->expectException(InvalidTokenException::class);

        $this->service()->resolveSubject($token);
    }

    public function test_the_issuer_claim_is_stamped_but_not_verified_on_resolution(): void
    {
        // Documents the current contract: the shared secret is what
        // authenticates a token — `iss` is informational only. A test
        // asserting rejection here would assert a rule that does not exist.
        $otherIssuer = new JwtTokenService(self::SECRET, 60, 'Some-Other-Issuer');
        $issued = $otherIssuer->issue(new FakeAuthenticatable(7));

        $this->assertSame(7, $this->service()->resolveSubject($issued->accessToken));
    }

    private function service(int $ttlMinutes = 60): JwtTokenService
    {
        return new JwtTokenService(self::SECRET, $ttlMinutes, self::ISSUER);
    }

    /**
     * @param  array<string, mixed>  $claims
     */
    private function encodeSegment(array $claims): string
    {
        return rtrim(strtr(base64_encode((string) json_encode($claims)), '+/', '-_'), '=');
    }
}
