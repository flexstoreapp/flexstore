<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Actions\ResetUserPasswordAction;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\Storefront\ShowResetPasswordRequest;
use App\Utilities\StorefrontHead;
use Illuminate\Container\Attributes\RouteParameter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final readonly class NewPasswordController
{
    public function create(#[RouteParameter('token')] string $token, ShowResetPasswordRequest $request): Response
    {
        StorefrontHead::page(__('Set a new password'));

        return Inertia::render('storefront/account/auth/reset-password', [
            'token' => $token,
            'email' => $request->safe()->string('email')->value(),
        ]);
    }

    public function store(ResetPasswordRequest $request, ResetUserPasswordAction $action): RedirectResponse
    {
        $status = $action->handle($request->toDto());

        if ($status === Password::PasswordReset) {
            return to_route('account.login')->with('message', __($status));
        }

        throw ValidationException::withMessages([
            'email' => [__($status)],
        ]);
    }
}
