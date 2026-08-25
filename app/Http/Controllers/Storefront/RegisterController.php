<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Actions\StoreCustomerAction;
use App\Http\Requests\Storefront\StoreAccountRegistrationRequest;
use App\Utilities\StorefrontHead;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

final readonly class RegisterController
{
    public function create(): Response
    {
        StorefrontHead::page(__('Create your account'));

        return Inertia::render('storefront/account/auth/register', [
        ]);
    }

    public function store(StoreAccountRegistrationRequest $request, StoreCustomerAction $action): RedirectResponse
    {
        $user = $action->handle($request->toDto());

        event(new Registered($user));

        Auth::login($user);

        return redirect()->intended(route('account.dashboard', absolute: false));
    }
}
