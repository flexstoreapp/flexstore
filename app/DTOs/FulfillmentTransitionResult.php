<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\FulfillmentStatus;
use App\Models\Order;

final readonly class FulfillmentTransitionResult
{
    public function __construct(
        public Order $order,
        public FulfillmentStatus $from,
    ) {
    }
}
