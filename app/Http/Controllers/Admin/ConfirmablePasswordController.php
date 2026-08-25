<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\ConfirmPasswordRequest;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final readonly class ConfirmablePasswordController
{
    public function show(): Response
    {
        return Inertia::render('admin/auth/confirm-password');
    }

    public function store(ConfirmPasswordRequest $request, #[CurrentUser] User $user): RedirectResponse
    {
        $valid = Auth::guard('web')->validate([
            'email' => $user->email,
            'password' => $request->safe()->string('password')->value(),
        ]);

        if (! $valid) {
            throw ValidationException::withMessages([
                'password' => __('auth.password'),
            ]);
        }

        $request->session()->put('auth.password_confirmed_at', time());

        return redirect()->intended(route('admin.dashboard', absolute: false));
    }
}
