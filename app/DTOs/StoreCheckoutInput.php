<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class StoreCheckoutInput
{
    public function __construct(
        public string $customerEmail,
        public ?int $paymentGatewayId,
        public ?int $shippingRateId,
        public ?string $shippingQuoteReference,
        public ?Address $shippingAddress,
        public bool $saveShippingAddress,
        public bool $differentBillingAddress,
        public ?Address $billingAddress,
        public ?string $notes,
        public ?string $couponCode,
        public ?string $cardToken,
        public ?string $currencyCode,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $differentBilling = (bool) ($data['different_billing_address'] ?? false);

        return new self(
            customerEmail: (string) $data['customer_email'],
            paymentGatewayId: isset($data['payment_gateway_id']) && $data['payment_gateway_id'] !== ''
                ? (int) $data['payment_gateway_id']
                : null,
            shippingRateId: isset($data['shipping_rate_id']) && $data['shipping_rate_id'] !== '' ? (int) $data['shipping_rate_id'] : null,
            shippingQuoteReference: isset($data['shipping_quote_reference']) ? (string) $data['shipping_quote_reference'] : null,
            shippingAddress: isset($data['shipping_address']) && is_array($data['shipping_address'])
                ? Address::fromArray($data['shipping_address'])
                : null,
            saveShippingAddress: (bool) ($data['save_shipping_address'] ?? false),
            differentBillingAddress: $differentBilling,
            billingAddress: isset($data['billing_address']) && is_array($data['billing_address'])
                ? Address::fromArray($data['billing_address'])
                : null,
            notes: isset($data['notes']) ? (string) $data['notes'] : null,
            couponCode: isset($data['coupon_code']) ? (string) $data['coupon_code'] : null,
            cardToken: isset($data['card_token']) ? (string) $data['card_token'] : null,
            currencyCode: isset($data['currency_code']) ? (string) $data['currency_code'] : null,
        );
    }
}
