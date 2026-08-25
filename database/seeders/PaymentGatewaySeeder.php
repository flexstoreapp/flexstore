<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\CheckoutMode;
use App\Enums\PaymentGatewayDriver;
use App\Models\PaymentGateway;
use Illuminate\Database\Seeder;

final class PaymentGatewaySeeder extends Seeder
{
    public function run(): void
    {
        $this->createGateway(PaymentGatewayDriver::Cod, [
            'en' => 'Cash on Delivery',
            'ar' => 'الدفع عند الاستلام',
        ]);

        $this->createGatewayFromEnv(
            PaymentGatewayDriver::Stripe,
            ['en' => 'Stripe', 'ar' => 'Stripe'],
            required: ['secret_key' => 'STRIPE_SECRET_KEY'],
            optional: ['publishable_key' => 'STRIPE_PUBLISHABLE_KEY'],
        );

        $this->createGatewayFromEnv(
            PaymentGatewayDriver::Paypal,
            ['en' => 'PayPal', 'ar' => 'PayPal'],
            required: ['client_id' => 'PAYPAL_CLIENT_ID', 'client_secret' => 'PAYPAL_CLIENT_SECRET'],
            extra: ['sandbox' => true],
        );

        $this->createGatewayFromEnv(
            PaymentGatewayDriver::Razorpay,
            ['en' => 'Razorpay', 'ar' => 'Razorpay'],
            required: ['key_id' => 'RAZORPAY_KEY_ID', 'key_secret' => 'RAZORPAY_KEY_SECRET'],
        );

        $this->createGatewayFromEnv(
            PaymentGatewayDriver::Tap,
            ['en' => 'Tap', 'ar' => 'Tap'],
            required: ['secret_key' => 'TAP_SECRET_KEY'],
            optional: ['public_key' => 'TAP_PUBLIC_KEY'],
        );

        $this->createGatewayFromEnv(
            PaymentGatewayDriver::Paystack,
            ['en' => 'Paystack', 'ar' => 'Paystack'],
            required: ['secret_key' => 'PAYSTACK_SECRET_KEY'],
            optional: ['public_key' => 'PAYSTACK_PUBLIC_KEY'],
        );

        $this->createGatewayFromEnv(
            PaymentGatewayDriver::MercadoPago,
            ['en' => 'Mercado Pago', 'ar' => 'Mercado Pago'],
            required: ['access_token' => 'MERCADOPAGO_ACCESS_TOKEN'],
            optional: [
                'public_key' => 'MERCADOPAGO_PUBLIC_KEY',
                'webhook_secret' => 'MERCADOPAGO_WEBHOOK_SECRET',
            ],
        );

        $this->createGatewayFromEnv(
            PaymentGatewayDriver::Mollie,
            ['en' => 'Mollie', 'ar' => 'Mollie'],
            required: ['api_key' => 'MOLLIE_API_KEY', 'profile_id' => 'MOLLIE_PROFILE_ID'],
        );
    }

    /**
     * @param  array<string, string>  $name
     * @param  array<string, string>  $required
     * @param  array<string, string>  $optional
     * @param  array<string, mixed>  $extra
     */
    private function createGatewayFromEnv(
        PaymentGatewayDriver $driver,
        array $name,
        array $required,
        array $optional = [],
        array $extra = [],
    ): void {
        $credentials = $extra;

        foreach ($required as $field => $envKey) {
            $value = env($envKey);

            if (! $value) {
                return;
            }

            $credentials[$field] = $value;
        }

        foreach ($optional as $field => $envKey) {
            $credentials[$field] = env($envKey);
        }

        $this->createGateway($driver, $name, $credentials);
    }

    /**
     * @param  array<string, string>  $name
     * @param  array<string, mixed>|null  $credentials
     */
    private function createGateway(PaymentGatewayDriver $driver, array $name, ?array $credentials = null): void
    {
        PaymentGateway::query()->create([
            'driver' => $driver,
            'name' => $name,
            'credentials' => $credentials === null ? null : [
                ...$credentials,
                'checkout_mode' => CheckoutMode::Embedded->value,
            ],
            'sync_external_refunds' => true,
            'is_active' => true,
        ]);
    }
}
