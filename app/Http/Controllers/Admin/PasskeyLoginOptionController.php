<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Passkeys\Actions\GenerateVerificationOptions;
use Laravel\Passkeys\Support\WebAuthn;

final readonly class PasskeyLoginOptionController
{
    public function show(Request $request, GenerateVerificationOptions $generate): JsonResponse
    {
        $options = $generate();

        $request->session()->put('passkey.verification_options', WebAuthn::toJson($options));

        return response()->json([
            'options' => WebAuthn::toBrowserArray($options),
        ]);
    }
}
