<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\DestroyCustomerAddressAction;
use App\Actions\StoreCustomerAddressAction;
use App\Actions\UpdateCustomerAddressAction;
use App\Http\Requests\StoreCustomerAddressRequest;
use App\Http\Requests\UpdateCustomerAddressRequest;
use App\Models\CustomerAddress;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

final readonly class CustomerAddressController
{
    public function store(
        StoreCustomerAddressRequest $request,
        User $customer,
        StoreCustomerAddressAction $action
    ): RedirectResponse {
        $action->handle($customer, $request->toDto());

        return back();
    }

    public function update(
        UpdateCustomerAddressRequest $request,
        User $customer,
        CustomerAddress $address,
        UpdateCustomerAddressAction $action
    ): RedirectResponse {
        abort_unless($address->user_id === $customer->id, 403);

        $action->handle($address, $request->toDto());

        return back();
    }

    public function destroy(
        User $customer,
        CustomerAddress $address,
        DestroyCustomerAddressAction $action
    ): RedirectResponse {
        abort_unless($address->user_id === $customer->id, 403);

        $action->handle($address);

        return back();
    }
}
