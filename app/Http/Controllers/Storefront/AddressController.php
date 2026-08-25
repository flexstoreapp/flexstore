<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Actions\StoreCustomerAddressAction;
use App\Actions\UpdateCustomerAddressAction;
use App\Http\Requests\StoreCustomerAddressRequest;
use App\Http\Requests\UpdateCustomerAddressRequest;
use App\Models\CustomerAddress;
use App\Models\User;
use App\Queries\CustomerAddressListQuery;
use App\Utilities\StorefrontHead;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final readonly class AddressController
{
    public function index(#[CurrentUser] User $user, CustomerAddressListQuery $query): Response
    {
        StorefrontHead::page(__('Addresses'));

        return Inertia::render('storefront/account/addresses/list', [
            'addresses' => $query->execute($user),
        ]);
    }

    public function store(
        StoreCustomerAddressRequest $request,
        #[CurrentUser] User $user,
        StoreCustomerAddressAction $action
    ): RedirectResponse {
        $action->handle($user, $request->toDto());

        return back();
    }

    public function update(
        UpdateCustomerAddressRequest $request,
        CustomerAddress $address,
        #[CurrentUser] User $user,
        UpdateCustomerAddressAction $action
    ): RedirectResponse {
        abort_unless($address->user_id === $user->id, 403);

        $action->handle($address, $request->toDto());

        return back();
    }

    public function destroy(CustomerAddress $address, #[CurrentUser] User $user): RedirectResponse
    {
        abort_unless($address->user_id === $user->id, 403);

        $address->delete();

        return back();
    }
}
