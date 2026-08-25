<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\ShippingCarrier;

final readonly class DestroyShippingCarrierAction
{
    public function handle(ShippingCarrier $carrier): bool
    {
        return (bool) $carrier->delete();
    }
}
