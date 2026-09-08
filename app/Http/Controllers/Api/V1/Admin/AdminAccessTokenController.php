<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\TokenAbility;
use App\Http\Requests\Api\V1\Admin\StoreAdminAccessTokenRequest;
use App\Http\Resources\Api\V1\Admin\AdminUserResource;
use App\Models\User;
use App\Utilities\LoginRateLimiter;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final readonly class AdminAccessTokenController
{
    private const int CHALLENGE_MINUTES = 5;

    public function store(
        StoreAdminAccessTokenRequest $request,
        LoginRateLimiter $rateLimiter,
    ): JsonResponse {
        $email = $request->safe()->string('email')->value();

        $rateLimiter->ensureNotRateLimited($email, $request);

        $user = User::query()->where('email', $email)->first();

        if (! $user instanceof User || ! Hash::check($request->safe()->string('password')->value(), $user->password)) {
            $rateLimiter->hit($email, $request);

            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        if (! $user->hasAdminAccess()) {
            $rateLimiter->hit($email, $request);

            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        $rateLimiter->clear($email, $request);

        if ($user->hasTwoFactorEnabled()) {
            $pending = $user->createToken(
                $request->safe()->string('device_name')->value(),
                [TokenAbility::TwoFactorPending->value],
                now()->addMinutes(self::CHALLENGE_MINUTES),
            );

            return response()->json([
                'two_factor_required' => true,
                'two_factor_token' => $pending->plainTextToken,
                'expires_in' => self::CHALLENGE_MINUTES * 60,
            ]);
        }

        $token = $user->createToken(
            $request->safe()->string('device_name')->value(),
            [TokenAbility::Admin->value],
        );

        return response()->json([
            'token' => $token->plainTextToken,
            'user' => new AdminUserResource($user),
        ]);
    }

    public function destroy(#[CurrentUser] User $user): JsonResponse
    {
        $user->currentAccessToken()->delete();

        return response()->json(['message' => __('Logged out.')]);
    }
}
