<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\ShippingRate;

final readonly class DestroyShippingRateAction
{
    public function handle(ShippingRate $shippingRate): bool
    {
        return (bool) $shippingRate->delete();
    }
}
