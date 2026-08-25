<?php

declare(strict_types=1);

namespace Src\IdentityAccess\Authentication\Presentation\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\IdentityAccess\Authentication\Domain\Contracts\TokenServiceInterface;
use Src\IdentityAccess\Authentication\Domain\Exceptions\TokenException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Stateless Bearer-token guard for routes/api.php. Every rejection reason
 * (missing, malformed, bad signature, expired) returns the same generic
 * 401 body so the response never reveals which check failed.
 */
final class AuthenticateJwt
{
    public function __construct(
        private readonly TokenServiceInterface $tokens,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! $token) {
            return $this->unauthenticated();
        }

        try {
            $userId = $this->tokens->resolveSubject($token);
        } catch (TokenException) {
            return $this->unauthenticated();
        }

        $user = User::query()->find($userId);

        if (! $user) {
            return $this->unauthenticated();
        }

        $request->setUserResolver(fn () => $user);

        return $next($request);
    }

    private function unauthenticated(): JsonResponse
    {
        return response()->json(['message' => __('Unauthenticated.')], 401);
    }
}
