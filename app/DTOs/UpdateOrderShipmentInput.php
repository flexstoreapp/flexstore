<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class UpdateOrderShipmentInput
{
    /**
     * @param  array<string, true>  $provided
     */
    public function __construct(
        public ?string $trackingNumber,
        public ?string $trackingUrl,
        public bool $notifyCustomer,
        public array $provided,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $keys = ['tracking_number', 'tracking_url'];
        $provided = [];
        foreach ($keys as $key) {
            if (array_key_exists($key, $data)) {
                $provided[$key] = true;
            }
        }

        return new self(
            trackingNumber: isset($data['tracking_number']) ? (string) $data['tracking_number'] : null,
            trackingUrl: isset($data['tracking_url']) ? (string) $data['tracking_url'] : null,
            notifyCustomer: (bool) ($data['notify_customer'] ?? false),
            provided: $provided,
        );
    }

    public function has(string $field): bool
    {
        return isset($this->provided[$field]);
    }
}
