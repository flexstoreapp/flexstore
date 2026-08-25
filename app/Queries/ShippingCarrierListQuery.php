<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\ShippingCarrier;
use Illuminate\Database\Eloquent\Collection;

final readonly class ShippingCarrierListQuery
{
    /**
     * @return Collection<int, ShippingCarrier>
     */
    public function execute(): Collection
    {
        return ShippingCarrier::query()
            ->get();
    }
}
