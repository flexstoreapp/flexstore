<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\SetDefaultCustomerAddressAction;
use App\Http\Resources\Api\V1\CustomerAddressResource;
use App\Models\CustomerAddress;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;

final readonly class SetDefaultCustomerAddressController
{
    public function __invoke(
        User $customer,
        CustomerAddress $address,
        SetDefaultCustomerAddressAction $action,
    ): CustomerAddressResource {
        abort_unless($address->user_id === $customer->id, Response::HTTP_FORBIDDEN);

        $action->handle($customer, $address);

        return new CustomerAddressResource($address->refresh());
    }
}
