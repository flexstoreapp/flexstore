<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\CheckoutOptionsInput;
use App\Enums\CheckoutSessionStatus;
use App\Models\CheckoutSession;
use App\Models\PaymentGateway;
use App\Models\ShippingRate;
use Brick\Math\BigDecimal;

final readonly class UpdatePendingCheckoutSessionAction
{
    public function __construct(
        private InitiateCheckoutSessionAction $initiateCheckoutSession,
    ) {
    }

    public function handle(string $cartId, CheckoutOptionsInput $input, ?string $customerEmail = null, ?int $customerId = null, ?string $currencyCode = null): ?CheckoutSession
    {
        $session = CheckoutSession::query()
            ->where('cart_id', $cartId)
            ->where('status', CheckoutSessionStatus::Pending)
            ->latest()
            ->latest('id')
            ->first();

        if (! $session instanceof CheckoutSession && $customerEmail) {
            $session = $this->initiateCheckoutSession->handle($cartId, $customerEmail, $customerId, $currencyCode);
        }

        if (! $session instanceof CheckoutSession) {
            return null;
        }

        $payload = [];

        if ($input->has('shipping_rate_id')) {
            $rate = $input->shippingRateId !== null
                ? ShippingRate::query()->with(['carrier:id,name', 'region:id,name'])->find($input->shippingRateId)
                : null;

            $payload['shipping_rate_id'] = $rate?->id;
            $payload['shipping_carrier_id'] = $rate?->shipping_carrier_id;
            $payload['shipping_rate_name'] = $rate?->getTranslations('name');
            $payload['shipping_carrier_name'] = $rate?->carrier?->getTranslations('name');
            $payload['region_id'] = $rate?->region_id;
            $payload['region_name'] = $rate?->region?->getTranslations('name');

            $payload['shipping_total'] = $rate instanceof ShippingRate ? ($rate->rate ?? '0.0000') : '0.0000';
        }

        if ($input->has('payment_gateway_id')) {
            $gateway = $input->paymentGatewayId !== null
                ? PaymentGateway::query()->find($input->paymentGatewayId)
                : null;

            $payload['payment_gateway_id'] = $gateway?->id;
            $payload['payment_gateway_name'] = $gateway?->getTranslations('name');
        }

        if ($input->has('shipping_address')) {
            $payload['shipping_address'] = $input->shippingAddress;
        }
        if ($input->has('billing_address')) {
            $payload['billing_address'] = $input->billingAddress;
        }
        if ($input->has('different_billing_address')) {
            $payload['different_billing_address'] = $input->differentBillingAddress;
        }
        if ($input->has('notes')) {
            $payload['notes'] = $input->notes;
        }
        if ($input->has('customer_address_id')) {
            $payload['customer_address_id'] = $input->customerAddressId;
        }

        if ($input->has('customer_email') && $input->customerEmail !== null && $input->customerEmail !== '') {
            $payload['customer_email'] = $input->customerEmail;
        }

        if ($customerId !== null && $session->customer_id !== $customerId) {
            $payload['customer_id'] = $customerId;
        }

        if (empty($payload)) {
            return $session;
        }

        if (array_key_exists('shipping_total', $payload)) {
            $payload['total'] = BigDecimal::of($session->subtotal)
                ->minus(BigDecimal::of($session->discount_total ?? '0'))
                ->plus(BigDecimal::of($payload['shipping_total']))
                ->plus(BigDecimal::of($session->tax_total ?? '0'))
                ->toScale(4)
                ->toString();
        }

        $session->updateReplacingTranslations($payload);

        return $session;
    }
}
