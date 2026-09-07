<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\TokenAbility;
use App\Http\Requests\Api\V1\StoreAccessTokenRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use App\Utilities\LoginRateLimiter;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final readonly class AccessTokenController
{
    public function store(StoreAccessTokenRequest $request, LoginRateLimiter $rateLimiter): JsonResponse
    {
        $email = $request->safe()->string('email')->value();

        $rateLimiter->ensureNotRateLimited($email, $request);

        $user = User::query()->where('email', $email)->first();

        if (! $user instanceof User || ! Hash::check($request->safe()->string('password')->value(), $user->password)) {
            $rateLimiter->hit($email, $request);

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $rateLimiter->clear($email, $request);

        $token = $user->createToken(
            $request->safe()->string('device_name')->value(),
            [TokenAbility::Customer->value],
        );

        return response()->json([
            'token' => $token->plainTextToken,
            'user' => new UserResource($user),
        ]);
    }

    public function destroy(#[CurrentUser] User $user): JsonResponse
    {
        $user->currentAccessToken()->delete();

        return response()->json(['message' => __('Logged out.')]);
    }
}
