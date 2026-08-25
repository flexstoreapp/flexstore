<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\PasskeyLoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Passkeys\Actions\VerifyPasskey;

final readonly class PasskeyLoginController
{
    public function store(PasskeyLoginRequest $request, VerifyPasskey $verify): JsonResponse
    {
        $passkey = $verify($request->credential(), $request->verificationOptions());

        $user = User::query()->findOrFail($passkey->user_id);

        Auth::guard('web')->login($user, $request->safe()->boolean('remember'));

        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        $request->session()->regenerate();

        return response()->json([
            'redirect' => route('admin.dashboard', absolute: false),
        ]);
    }
}
