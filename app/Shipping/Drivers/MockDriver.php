<?php

declare(strict_types=1);

namespace App\Shipping\Drivers;

use App\Shipping\Contracts\ShippingDriver;

final readonly class MockDriver implements ShippingDriver
{
    public function __construct(private bool $collectsCod = false)
    {
    }

    public function collectsCod(): bool
    {
        return $this->collectsCod;
    }

    public function isManual(): bool
    {
        return true;
    }
}
