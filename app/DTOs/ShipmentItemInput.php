<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class ShipmentItemInput
{
    public function __construct(
        public int $orderItemId,
        public int $quantity,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            orderItemId: (int) $data['order_item_id'],
            quantity: (int) $data['quantity'],
        );
    }
}
