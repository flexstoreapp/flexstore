<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\CustomerAddress;

final readonly class DestroyCustomerAddressAction
{
    public function handle(CustomerAddress $address): bool
    {
        return (bool) $address->delete();
    }
}
