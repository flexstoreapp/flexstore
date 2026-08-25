<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\SetDefaultCustomerAddressAction;
use App\Models\CustomerAddress;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

final readonly class SetDefaultCustomerAddressController
{
    public function __invoke(
        User $customer,
        CustomerAddress $address,
        SetDefaultCustomerAddressAction $action,
    ): RedirectResponse {
        abort_unless($address->user_id === $customer->id, 403);

        $action->handle($customer, $address);

        return back();
    }
}
