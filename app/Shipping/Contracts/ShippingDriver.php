<?php

declare(strict_types=1);

namespace App\Shipping\Contracts;

interface ShippingDriver
{
    public function collectsCod(): bool;

    public function isManual(): bool;
}
