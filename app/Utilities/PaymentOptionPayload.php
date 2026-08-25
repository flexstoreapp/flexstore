<?php

declare(strict_types=1);

namespace App\Utilities;

use App\Enums\CheckoutMode;
use App\Enums\PaymentGatewayDriver;
use App\Models\PaymentGateway;

final readonly class PaymentOptionPayload
{
    /**
     * @return array<string, mixed>
     */
    public static function fromGateway(PaymentGateway $gateway): array
    {
        return [
            'id' => $gateway->id,
            'name' => $gateway->getTranslations('name'),
            'driver' => $gateway->driver->value,
            ...self::embeddedFields($gateway),
        ];
    }

    /**
     * @return array<string, bool|string|null>
     */
    private static function embeddedFields(PaymentGateway $gateway): array
    {
        $credentials = $gateway->credentials ?? [];

        if (($credentials['checkout_mode'] ?? CheckoutMode::Embedded->value) !== CheckoutMode::Embedded->value) {
            return [];
        }

        $fields = match ($gateway->driver) {
            PaymentGatewayDriver::Stripe => ['publishable_key' => $credentials['publishable_key'] ?? null],
            PaymentGatewayDriver::Paypal => ['client_id' => $credentials['client_id'] ?? null],
            PaymentGatewayDriver::Razorpay => ['key_id' => $credentials['key_id'] ?? null],
            PaymentGatewayDriver::Mollie => [
                'profile_id' => $credentials['profile_id'] ?? null,
                'testmode' => str_starts_with($credentials['api_key'] ?? '', 'test_'),
            ],
            PaymentGatewayDriver::Tap, PaymentGatewayDriver::Paystack, PaymentGatewayDriver::MercadoPago => ['public_key' => $credentials['public_key'] ?? null],
            default => [],
        };

        if ($fields === []) {
            return [];
        }

        return ['checkout_mode' => CheckoutMode::Embedded->value, ...$fields];
    }
}
