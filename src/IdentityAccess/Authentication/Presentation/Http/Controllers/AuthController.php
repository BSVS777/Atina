<?php

declare(strict_types=1);

namespace Src\IdentityAccess\Authentication\Presentation\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Src\IdentityAccess\Authentication\Application\UseCases\AuthenticateUserUseCase;
use Src\IdentityAccess\Authentication\Domain\Exceptions\InvalidCredentialsException;
use Src\IdentityAccess\Authentication\Presentation\Http\Requests\LoginRequest;

final class AuthController extends Controller
{
    public function __construct(
        private readonly AuthenticateUserUseCase $authenticateUser,
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $issued = $this->authenticateUser->handle(
                $request->string('email')->toString(),
                $request->string('password')->toString(),
            );
        } catch (InvalidCredentialsException) {
            return response()->json(['message' => __('These credentials do not match our records.')], 401);
        }

        return response()->json([
            'access_token' => $issued->accessToken,
            'token_type' => $issued->tokenType,
            'expires_in' => $issued->expiresIn,
        ]);
    }

    /**
     * The jwt.auth middleware sets the request's user resolver — $user is
     * guaranteed present by the time this action runs.
     */
    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->loadMissing(['roles.permissions', 'permissions']);

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $user->roles->pluck('name')->values(),
            'permissions' => $user->allPermissionNames(),
        ]);
    }
}
