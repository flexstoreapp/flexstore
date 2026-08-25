<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Actions\SendPasswordResetLinkAction;
use App\Http\Requests\SendPasswordResetLinkRequest;
use App\Utilities\StorefrontHead;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final readonly class PasswordResetLinkController
{
    public function create(): Response
    {
        StorefrontHead::page(__('Reset your password'));

        return Inertia::render('storefront/account/auth/forgot-password');
    }

    public function store(SendPasswordResetLinkRequest $request, SendPasswordResetLinkAction $action): RedirectResponse
    {
        $action->handle($request->toDto());

        return back()->with('message', __('A reset link will be sent if the account exists.'));
    }
}
