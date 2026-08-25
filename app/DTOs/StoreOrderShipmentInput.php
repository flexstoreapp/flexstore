<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class StoreOrderShipmentInput
{
    /**
     * @param  list<ShipmentItemInput>  $items
     */
    public function __construct(
        public ?string $trackingNumber,
        public ?string $trackingUrl,
        public array $items,
        public bool $notifyCustomer,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $items = [];
        foreach ($data['items'] ?? [] as $item) {
            $items[] = ShipmentItemInput::fromArray($item);
        }

        return new self(
            trackingNumber: isset($data['tracking_number']) ? (string) $data['tracking_number'] : null,
            trackingUrl: isset($data['tracking_url']) ? (string) $data['tracking_url'] : null,
            items: $items,
            notifyCustomer: (bool) ($data['notify_customer'] ?? false),
        );
    }
}
