<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

final readonly class EmailVerificationNotificationController
{
    public function __invoke(#[CurrentUser] User $user): JsonResponse
    {
        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => __('Your email address is already verified.')]);
        }

        $user->sendEmailVerificationNotification();

        return response()->json(['message' => __('A new verification link has been sent to your email address.')]);
    }
}
