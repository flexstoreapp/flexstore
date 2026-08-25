<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\StoreCustomerAddressInput;
use App\Models\CustomerAddress;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class StoreCustomerAddressAction
{
    public function handle(User $customer, StoreCustomerAddressInput $input): CustomerAddress
    {
        return DB::transaction(function () use ($customer, $input): CustomerAddress {
            if ($input->isDefault) {
                $customer->addresses()->update(['is_default' => false]);
            }

            return $customer->addresses()->create([
                'first_name' => $input->address->firstName,
                'last_name' => $input->address->lastName,
                'address_line_1' => $input->address->addressLine1,
                'address_line_2' => $input->address->addressLine2,
                'city' => $input->address->city,
                'state' => $input->address->state,
                'postal_code' => $input->address->postalCode,
                'country_code' => $input->address->countryCode,
                'phone' => $input->address->phone,
                'is_default' => $input->isDefault,
            ]);
        });
    }
}
