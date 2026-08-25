<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class StoreOrderInput
{
    /**
     * @param  list<OrderItemInput>  $items
     */
    public function __construct(
        public string $customerEmail,
        public ?int $customerId,
        public ?string $currencyCode,
        public ?int $shippingRateId,
        public ?int $paymentGatewayId,
        public ?string $couponCode,
        public ?string $notes,
        public array $items,
        public ?Address $shippingAddress,
        public bool $differentBillingAddress,
        public ?Address $billingAddress,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $items = [];
        foreach ($data['items'] ?? [] as $item) {
            $items[] = OrderItemInput::fromArray($item);
        }

        $differentBilling = (bool) ($data['different_billing_address'] ?? false);

        return new self(
            customerEmail: (string) $data['customer_email'],
            customerId: isset($data['customer_id']) ? (int) $data['customer_id'] : null,
            currencyCode: isset($data['currency_code']) ? (string) $data['currency_code'] : null,
            shippingRateId: isset($data['shipping_rate_id']) ? (int) $data['shipping_rate_id'] : null,
            paymentGatewayId: isset($data['payment_gateway_id']) ? (int) $data['payment_gateway_id'] : null,
            couponCode: isset($data['coupon_code']) ? (string) $data['coupon_code'] : null,
            notes: isset($data['notes']) ? (string) $data['notes'] : null,
            items: $items,
            shippingAddress: isset($data['shipping_address']) && is_array($data['shipping_address'])
                ? Address::fromArray($data['shipping_address'])
                : null,
            differentBillingAddress: $differentBilling,
            billingAddress: isset($data['billing_address']) && is_array($data['billing_address'])
                ? Address::fromArray($data['billing_address'])
                : null,
        );
    }
}
