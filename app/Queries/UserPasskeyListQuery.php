<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\User;
use Laravel\Passkeys\Passkey;

final readonly class UserPasskeyListQuery
{
    /**
     * @return array<int, array{id: int, name: string, authenticator: string|null, created_at_diff: string|null, last_used_at_diff: string|null}>
     */
    public function execute(User $user): array
    {
        return $user->passkeys()
            ->latest()
            ->get()
            ->map(fn (Passkey $passkey): array => [
                'id' => $passkey->id,
                'name' => $passkey->name,
                'authenticator' => $passkey->authenticator,
                'created_at_diff' => $passkey->created_at?->diffForHumans(),
                'last_used_at_diff' => $passkey->last_used_at?->diffForHumans(),
            ])
            ->all();
    }
}
