<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\CustomerAddress;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class SetDefaultCustomerAddressAction
{
    public function handle(User $user, CustomerAddress $address): void
    {
        DB::transaction(function () use ($user, $address): void {
            $user->addresses()->update(['is_default' => false]);
            $address->update(['is_default' => true]);
        });
    }
}
