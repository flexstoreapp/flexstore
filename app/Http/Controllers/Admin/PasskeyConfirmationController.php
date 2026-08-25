<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\ConfirmPasskeyRequest;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Laravel\Passkeys\Actions\VerifyPasskey;

final readonly class PasskeyConfirmationController
{
    public function store(ConfirmPasskeyRequest $request, #[CurrentUser] User $user, VerifyPasskey $verify): JsonResponse
    {
        $verify($request->credential(), $request->verificationOptions(), $user);

        $request->session()->put('auth.password_confirmed_at', time());

        return response()->json([
            'redirect' => redirect()->intended(route('admin.dashboard', absolute: false))->getTargetUrl(),
        ]);
    }
}
