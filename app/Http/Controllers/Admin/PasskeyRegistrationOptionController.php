<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Passkeys\Actions\GenerateRegistrationOptions;
use Laravel\Passkeys\Support\WebAuthn;

final readonly class PasskeyRegistrationOptionController
{
    public function show(Request $request, #[CurrentUser] User $user, GenerateRegistrationOptions $generate): JsonResponse
    {
        $options = $generate($user);

        $request->session()->put('passkey.registration_options', WebAuthn::toJson($options));

        return response()->json([
            'options' => WebAuthn::toBrowserArray($options),
        ]);
    }
}
