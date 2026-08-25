<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\UpdateShippingCarrierInput;
use App\Models\ShippingCarrier;

final readonly class UpdateShippingCarrierAction
{
    public function handle(ShippingCarrier $carrier, UpdateShippingCarrierInput $input): ShippingCarrier
    {
        $driver = $input->has('driver') ? $input->driver : $carrier->driver;

        $carrier->update([
            'name' => $input->has('name') ? $input->name : $carrier->name,
            'driver' => $driver,
            'is_active' => $input->has('is_active') ? $input->isActive : $carrier->is_active,
        ]);

        return $carrier;
    }
}
