<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\VerifyEmailRequest;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * The emailed link points at the web route, so a native client sends the `id` and
 * `hash` it carries here instead of following it.
 */
final readonly class VerifyEmailController
{
    public function __invoke(VerifyEmailRequest $request, #[CurrentUser] User $user): JsonResponse
    {
        $matches = $request->safe()->integer('id') === $user->id
            && hash_equals(
                hash('sha1', (string) $user->getEmailForVerification()),
                $request->safe()->string('hash')->value(),
            );

        abort_unless($matches, Response::HTTP_FORBIDDEN, __('This verification link is not valid for your account.'));

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => __('Your email address is already verified.')]);
        }

        $user->markEmailAsVerified();

        event(new Verified($user));

        return response()->json(['message' => __('Your email address is verified.')]);
    }
}
