<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CheckoutMode;
use App\Enums\PaymentGatewayDriver;
use App\Enums\WeightUnit;
use App\Models\PaymentGateway;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PaymentGateway>
 */
#[UseModel(PaymentGateway::class)]
final class PaymentGatewayFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $driver = fake()->randomElement(PaymentGatewayDriver::cases());

        return [
            'name' => $driver->name,
            'driver' => $driver->value,
            'sync_external_refunds' => true,
            'is_active' => fake()->boolean(80),
        ];
    }

    public function active(): self
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => true,
        ]);
    }

    public function inactive(): self
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    public function withConditions(): self
    {
        return $this->state(fn (array $attributes): array => [
            'min_order_value' => fake()->randomFloat(2, 0, 50),
            'max_order_value' => fake()->randomFloat(2, 50, 200),
            'min_weight' => fake()->randomFloat(2, 0, 5),
            'min_weight_unit' => fake()->randomElement(WeightUnit::cases()),
            'max_weight' => fake()->randomFloat(2, 5, 20),
            'max_weight_unit' => fake()->randomElement(WeightUnit::cases()),
        ]);
    }

    public function withRestrictions(): self
    {
        return $this->state(fn (array $attributes): array => [
            'excluded_products' => [1, 2, 3],
            'excluded_categories' => [1, 2],
            'excluded_brands' => [1, 2],
            'allowed_regions' => [1, 2, 3, 4],
        ]);
    }

    public function stripe(): self
    {
        return $this->state(fn (array $attributes): array => [
            'driver' => PaymentGatewayDriver::Stripe,
            'name' => 'Stripe',
            'credentials' => [
                'publishable_key' => 'pk_test_' . Str::random(24),
                'secret_key' => 'sk_test_' . Str::random(24),
            ],
        ]);
    }

    public function paypal(): self
    {
        return $this->state(fn (array $attributes): array => [
            'driver' => PaymentGatewayDriver::Paypal,
            'name' => 'PayPal',
            'credentials' => [
                'client_id' => 'test_client_id_' . Str::random(24),
                'client_secret' => 'test_client_secret_' . Str::random(24),
                'sandbox' => true,
            ],
        ]);
    }

    public function razorpay(): self
    {
        return $this->state(fn (array $attributes): array => [
            'driver' => PaymentGatewayDriver::Razorpay,
            'name' => 'Razorpay',
            'credentials' => [
                'key_id' => 'rzp_test_' . Str::random(14),
                'key_secret' => Str::random(24),
            ],
        ]);
    }

    public function mollie(): self
    {
        return $this->state(fn (array $attributes): array => [
            'driver' => PaymentGatewayDriver::Mollie,
            'name' => 'Mollie',
            'credentials' => [
                'api_key' => 'test_' . Str::random(30),
                'profile_id' => 'pfl_' . Str::random(10),
                'checkout_mode' => CheckoutMode::Embedded->value,
            ],
        ]);
    }

    public function tap(): self
    {
        return $this->state(fn (array $attributes): array => [
            'driver' => PaymentGatewayDriver::Tap,
            'name' => 'Tap',
            'credentials' => [
                'public_key' => 'pk_test_' . Str::random(24),
                'secret_key' => 'sk_test_' . Str::random(24),
                'checkout_mode' => CheckoutMode::Embedded->value,
            ],
        ]);
    }

    public function paystack(): self
    {
        return $this->state(fn (array $attributes): array => [
            'driver' => PaymentGatewayDriver::Paystack,
            'name' => 'Paystack',
            'credentials' => [
                'public_key' => 'pk_test_' . Str::random(24),
                'secret_key' => 'sk_test_' . Str::random(24),
                'checkout_mode' => CheckoutMode::Embedded->value,
            ],
        ]);
    }

    public function mercadoPago(): self
    {
        return $this->state(fn (array $attributes): array => [
            'driver' => PaymentGatewayDriver::MercadoPago,
            'name' => 'Mercado Pago',
            'credentials' => [
                'public_key' => 'TEST-' . Str::random(24),
                'access_token' => 'TEST-' . Str::random(30),
                'webhook_secret' => Str::random(32),
                'checkout_mode' => CheckoutMode::Embedded->value,
            ],
        ]);
    }

    public function cod(): self
    {
        return $this->state(fn (array $attributes): array => [
            'driver' => PaymentGatewayDriver::Cod,
            'name' => 'Cash on Delivery',
        ]);
    }
}
