<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\ResetUserPasswordAction;
use App\Http\Requests\ResetPasswordRequest;
use Illuminate\Container\Attributes\RouteParameter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final readonly class NewPasswordController
{
    public function create(
        #[RouteParameter('token')] string $token,
        Request $request,
    ): Response {
        return Inertia::render('admin/auth/reset-password', [
            'email' => $request->email,
            'token' => $token,
        ]);
    }

    public function store(ResetPasswordRequest $request, ResetUserPasswordAction $action): RedirectResponse
    {
        $status = $action->handle($request->toDto());

        if ($status === Password::PasswordReset) {
            return to_route('admin.login')->with('status', __($status));
        }

        throw ValidationException::withMessages([
            'email' => [__($status)],
        ]);
    }
}
