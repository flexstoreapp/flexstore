<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Actions\SetDefaultCustomerAddressAction;
use App\Models\CustomerAddress;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;

final readonly class SetDefaultAddressController
{
    public function __invoke(
        CustomerAddress $address,
        #[CurrentUser] User $user,
        SetDefaultCustomerAddressAction $action,
    ): RedirectResponse {
        abort_unless($user->id === $address->user_id, 403);

        $action->handle($user, $address);

        return back();
    }
}
