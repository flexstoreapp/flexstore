<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\VerifyEmailRequest;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * The emailed link points at the web route, so a native client sends the whole query
 * it carries here instead of following it. The signature is what proves the customer
 * opened the email; the token only says who is asking.
 */
final readonly class VerifyEmailController
{
    public function __invoke(VerifyEmailRequest $request, #[CurrentUser] User $user): JsonResponse
    {
        abort_unless(
            $this->linkWasIssuedByUs($request),
            Response::HTTP_FORBIDDEN,
            __('This verification link is not valid for your account.'),
        );

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

    private function linkWasIssuedByUs(VerifyEmailRequest $request): bool
    {
        $link = URL::route('account.verification.verify', [
            'id' => $request->safe()->integer('id'),
            'hash' => $request->safe()->string('hash')->value(),
            'expires' => $request->safe()->integer('expires'),
            'signature' => $request->safe()->string('signature')->value(),
        ]);

        return URL::hasValidSignature(Request::create($link));
    }
}
