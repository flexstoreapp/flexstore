<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class UpdateCartInput
{
    /**
     * @param  array<string, true>  $provided
     */
    public function __construct(
        public ?int $shippingRateId,
        public ?string $shippingQuoteReference,
        public ?int $paymentGatewayId,
        public array $provided,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $provided = [];
        foreach (['shipping_rate_id', 'shipping_quote_reference', 'payment_gateway_id'] as $key) {
            if (array_key_exists($key, $data)) {
                $provided[$key] = true;
            }
        }

        return new self(
            shippingRateId: isset($data['shipping_rate_id']) ? (int) $data['shipping_rate_id'] : null,
            shippingQuoteReference: isset($data['shipping_quote_reference']) ? (string) $data['shipping_quote_reference'] : null,
            paymentGatewayId: isset($data['payment_gateway_id']) ? (int) $data['payment_gateway_id'] : null,
            provided: $provided,
        );
    }

    public function has(string $field): bool
    {
        return isset($this->provided[$field]);
    }
}
