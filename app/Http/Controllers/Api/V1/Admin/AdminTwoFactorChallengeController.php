<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\CompleteTwoFactorLoginAction;
use App\DTOs\TwoFactorChallengeInput;
use App\Enums\TokenAbility;
use App\Http\Requests\Api\V1\Admin\StoreTwoFactorChallengeRequest;
use App\Http\Resources\Api\V1\Admin\AdminUserResource;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Second step of the admin login: the pending token proves the password was
 * accepted, and is exchanged here for a token that can reach the API.
 */
final readonly class AdminTwoFactorChallengeController
{
    public function __invoke(
        StoreTwoFactorChallengeRequest $request,
        #[CurrentUser] User $user,
        CompleteTwoFactorLoginAction $action,
    ): JsonResponse {
        abort_unless($user->hasTwoFactorEnabled(), Response::HTTP_NOT_FOUND);

        $action->handle(
            $user,
            TwoFactorChallengeInput::fromArray($request->safe()->only(['code', 'recovery_code'])),
            $request->ip() ?? '',
        );

        $user->currentAccessToken()->delete();

        $token = $user->createToken(
            $request->safe()->string('device_name')->value(),
            [TokenAbility::Admin->value],
        );

        return response()->json([
            'token' => $token->plainTextToken,
            'user' => new AdminUserResource($user),
        ]);
    }
}
