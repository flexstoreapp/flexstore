<?php

declare(strict_types=1);

namespace App\Queries;

use App\DTOs\AddressLocation;
use App\DTOs\OrderItemsSummary;
use Illuminate\Support\Collection;

final readonly class CheckoutShippingOptionsQuery
{
    public function __construct(private EligibleShippingOptionsQuery $eligibleShippingOptionsQuery)
    {
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function execute(
        OrderItemsSummary $summary,
        ?AddressLocation $location,
    ): Collection {
        if (! $summary->requiresShipping()) {
            return collect();
        }

        return $this->eligibleShippingOptionsQuery->execute($summary, $location)->values();
    }
}
