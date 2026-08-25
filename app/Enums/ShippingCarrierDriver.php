<?php

declare(strict_types=1);

namespace App\Enums;

use App\Models\ShippingCarrier;
use App\Shipping\Contracts\ShippingDriver;
use App\Shipping\Drivers\ManualDriver;

enum ShippingCarrierDriver: string
{
    case Manual = 'manual';

    public function make(ShippingCarrier $carrier): ShippingDriver
    {
        return match ($this) {
            self::Manual => new ManualDriver(),
        };
    }
}
