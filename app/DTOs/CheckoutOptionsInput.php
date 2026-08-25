<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class CheckoutOptionsInput
{
    /**
     * @param  array<string, mixed>|null  $shippingAddress
     * @param  array<string, mixed>|null  $billingAddress
     * @param  array<string, true>  $provided
     */
    public function __construct(
        public ?int $shippingRateId,
        public ?string $shippingQuoteReference,
        public ?int $paymentGatewayId,
        public ?array $shippingAddress,
        public ?array $billingAddress,
        public ?bool $differentBillingAddress,
        public ?string $notes,
        public ?string $customerEmail,
        public ?int $customerAddressId,
        public array $provided,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $keys = ['shipping_rate_id', 'shipping_quote_reference', 'payment_gateway_id', 'shipping_address', 'billing_address', 'different_billing_address', 'notes', 'customer_email', 'customer_address_id'];
        $provided = [];
        foreach ($keys as $key) {
            if (array_key_exists($key, $data)) {
                $provided[$key] = true;
            }
        }

        return new self(
            shippingRateId: isset($data['shipping_rate_id']) ? (int) $data['shipping_rate_id'] : null,
            shippingQuoteReference: isset($data['shipping_quote_reference']) ? (string) $data['shipping_quote_reference'] : null,
            paymentGatewayId: isset($data['payment_gateway_id']) ? (int) $data['payment_gateway_id'] : null,
            shippingAddress: isset($data['shipping_address']) && is_array($data['shipping_address']) ? $data['shipping_address'] : null,
            billingAddress: isset($data['billing_address']) && is_array($data['billing_address']) ? $data['billing_address'] : null,
            differentBillingAddress: isset($data['different_billing_address']) ? (bool) $data['different_billing_address'] : null,
            notes: isset($data['notes']) ? (string) $data['notes'] : null,
            customerEmail: isset($data['customer_email']) ? (string) $data['customer_email'] : null,
            customerAddressId: isset($data['customer_address_id']) ? (int) $data['customer_address_id'] : null,
            provided: $provided,
        );
    }

    public function has(string $field): bool
    {
        return isset($this->provided[$field]);
    }
}
