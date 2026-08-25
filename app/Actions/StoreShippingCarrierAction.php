<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\StoreShippingCarrierInput;
use App\Models\ShippingCarrier;

final readonly class StoreShippingCarrierAction
{
    public function handle(StoreShippingCarrierInput $input): ShippingCarrier
    {
        return ShippingCarrier::query()->create([
            'name' => $input->name,
            'driver' => $input->driver,
            'is_active' => $input->isActive,
        ]);
    }
}
