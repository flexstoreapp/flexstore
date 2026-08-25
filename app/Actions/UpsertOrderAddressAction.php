<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\Address;
use App\Enums\OrderAddressType;
use App\Models\Order;
use App\Models\OrderAddress;

final readonly class UpsertOrderAddressAction
{
    public function handle(Order $order, Address $address, OrderAddressType $type): OrderAddress
    {
        return $order->addresses()->updateOrCreate(
            ['type' => $type],
            [
                'first_name' => $address->firstName,
                'last_name' => $address->lastName,
                'address_line_1' => $address->addressLine1,
                'address_line_2' => $address->addressLine2,
                'city' => $address->city,
                'state' => $address->state,
                'postal_code' => $address->postalCode,
                'country_code' => $address->countryCode,
                'phone' => $address->phone,
            ]
        );
    }
}
