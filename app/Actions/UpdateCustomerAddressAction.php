<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\UpdateCustomerAddressInput;
use App\Models\CustomerAddress;
use Illuminate\Support\Facades\DB;

final readonly class UpdateCustomerAddressAction
{
    public function handle(CustomerAddress $address, UpdateCustomerAddressInput $input): CustomerAddress
    {
        $isDefault = $input->has('is_default') ? (bool) $input->isDefault : $address->is_default;

        DB::transaction(function () use ($address, $input, $isDefault): void {
            if ($isDefault && ! $address->is_default) {
                $address->user->addresses()->whereKeyNot($address)->update(['is_default' => false]);
            }

            $address->update([
                'first_name' => $input->has('first_name') ? $input->firstName : $address->first_name,
                'last_name' => $input->has('last_name') ? $input->lastName : $address->last_name,
                'address_line_1' => $input->has('address_line_1') ? $input->addressLine1 : $address->address_line_1,
                'address_line_2' => $input->has('address_line_2') ? $input->addressLine2 : $address->address_line_2,
                'city' => $input->has('city') ? $input->city : $address->city,
                'state' => $input->has('state') ? $input->state : $address->state,
                'postal_code' => $input->has('postal_code') ? $input->postal_code : $address->postal_code,
                'country_code' => $input->has('country_code') ? $input->countryCode : $address->country_code,
                'phone' => $input->has('phone') ? $input->phone : $address->phone,
                'is_default' => $isDefault,
            ]);
        });

        return $address;
    }
}
