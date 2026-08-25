<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class UpdateOrderInput
{
    /**
     * @param  list<OrderItemInput>|null  $items
     * @param  array<string, true>  $provided
     */
    public function __construct(
        public ?int $customerId,
        public ?string $customerEmail,
        public ?string $currencyCode,
        public ?int $shippingRateId,
        public ?int $paymentGatewayId,
        public ?string $notes,
        public ?string $couponCode,
        public ?array $items,
        public ?Address $shippingAddress,
        public ?bool $differentBillingAddress,
        public ?Address $billingAddress,
        public ?bool $notifyCustomer,
        public ?bool $restock,
        public array $provided,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $keys = ['customer_id', 'customer_email', 'currency_code', 'shipping_rate_id', 'payment_gateway_id', 'notes', 'coupon_code', 'items', 'shipping_address', 'different_billing_address', 'billing_address', 'notify_customer', 'restock'];
        $provided = [];
        foreach ($keys as $key) {
            if (array_key_exists($key, $data)) {
                $provided[$key] = true;
            }
        }

        $items = null;
        if (isset($data['items']) && is_array($data['items'])) {
            $items = [];
            foreach ($data['items'] as $item) {
                $items[] = OrderItemInput::fromArray($item);
            }
        }

        return new self(
            customerId: isset($data['customer_id']) ? (int) $data['customer_id'] : null,
            customerEmail: isset($data['customer_email']) ? (string) $data['customer_email'] : null,
            currencyCode: isset($data['currency_code']) ? (string) $data['currency_code'] : null,
            shippingRateId: isset($data['shipping_rate_id']) ? (int) $data['shipping_rate_id'] : null,
            paymentGatewayId: isset($data['payment_gateway_id']) ? (int) $data['payment_gateway_id'] : null,
            notes: isset($data['notes']) ? (string) $data['notes'] : null,
            couponCode: isset($data['coupon_code']) ? (string) $data['coupon_code'] : null,
            items: $items,
            shippingAddress: isset($data['shipping_address']) && is_array($data['shipping_address']) ? Address::fromArray($data['shipping_address']) : null,
            differentBillingAddress: isset($data['different_billing_address']) ? (bool) $data['different_billing_address'] : null,
            billingAddress: isset($data['billing_address']) && is_array($data['billing_address']) ? Address::fromArray($data['billing_address']) : null,
            notifyCustomer: isset($data['notify_customer']) ? (bool) $data['notify_customer'] : null,
            restock: isset($data['restock']) ? (bool) $data['restock'] : null,
            provided: $provided,
        );
    }

    public function has(string $field): bool
    {
        return isset($this->provided[$field]);
    }
}
