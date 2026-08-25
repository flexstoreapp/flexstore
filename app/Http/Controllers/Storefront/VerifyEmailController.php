<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

final readonly class VerifyEmailController
{
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        $request->fulfill();

        return redirect()->intended(route('account.dashboard', absolute: false) . '?verified=1');
    }
}
