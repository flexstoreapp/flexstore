<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\StorePasskeyRequest;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Laravel\Passkeys\Actions\DeletePasskey;
use Laravel\Passkeys\Actions\StorePasskey;
use Laravel\Passkeys\Passkey;

final readonly class PasskeyController
{
    public function store(StorePasskeyRequest $request, #[CurrentUser] User $user, StorePasskey $storePasskey): JsonResponse
    {
        $passkey = $storePasskey(
            $user,
            $request->safe()->string('name')->value(),
            $request->credential(),
            $request->registrationOptions(),
        );

        return response()->json([
            'id' => $passkey->id,
            'name' => $passkey->name,
        ]);
    }

    public function destroy(Passkey $passkey, #[CurrentUser] User $user, DeletePasskey $deletePasskey): RedirectResponse
    {
        abort_unless($passkey->user_id === $user->id, 403);

        $deletePasskey($user, $passkey);

        return back();
    }
}
