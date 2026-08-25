<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class StoreOrderRefundInput
{
    /**
     * @param  list<OrderRefundItemInput>  $items
     */
    public function __construct(
        public array $items,
        public string $shippingAmount,
        public string $restockingFee,
        public bool $restock,
        public bool $isManualTotal,
        public ?string $total,
        public ?string $reason,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $items = [];
        foreach ($data['items'] ?? [] as $item) {
            $items[] = OrderRefundItemInput::fromArray($item);
        }

        return new self(
            items: $items,
            shippingAmount: isset($data['shipping_amount']) ? (string) $data['shipping_amount'] : '0.0000',
            restockingFee: isset($data['restocking_fee']) ? (string) $data['restocking_fee'] : '0.0000',
            restock: (bool) ($data['restock'] ?? true),
            isManualTotal: (bool) ($data['is_manual_total'] ?? false),
            total: isset($data['total']) ? (string) $data['total'] : null,
            reason: isset($data['reason']) ? (string) $data['reason'] : null,
        );
    }
}
