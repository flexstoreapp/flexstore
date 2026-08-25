<?php

declare(strict_types=1);

namespace App\Shipping\Drivers;

use App\Shipping\Contracts\ShippingDriver;

final readonly class ManualDriver implements ShippingDriver
{
    public function collectsCod(): bool
    {
        return false;
    }

    public function isManual(): bool
    {
        return true;
    }
}
