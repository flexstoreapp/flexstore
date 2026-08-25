<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CheckoutSessionStatus;
use App\Enums\CheckoutStep;
use App\Enums\TaxBasedOn;
use App\Models\Cart;
use App\Models\CheckoutSession;
use App\Models\PaymentGateway;
use App\Models\Setting;
use App\Models\ShippingCarrier;
use App\Models\ShippingRate;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CheckoutSession>
 */
#[UseModel(CheckoutSession::class)]
final class CheckoutSessionFactory extends Factory
{
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 10, 500);
        $taxAmount = round($subtotal * 0.1, 2);
        $shippingAmount = fake()->randomFloat(2, 0, 25);
        $discountAmount = fake()->randomFloat(2, 0, $subtotal * 0.2);
        $total = round($subtotal + $taxAmount + $shippingAmount - $discountAmount, 2);

        $shippingCarrier = ShippingCarrier::query()->inRandomOrder()->first()
            ?? ShippingCarrier::factory()->create();
        $shippingRate = ShippingRate::query()->inRandomOrder()->first()
            ?? ShippingRate::factory()->create();
        $paymentGateway = PaymentGateway::query()->inRandomOrder()->first()
            ?? PaymentGateway::factory()->create();

        return [
            'cart_id' => Cart::factory(),
            'customer_id' => null,
            'customer_email' => fake()->email(),
            'shipping_address' => $this->fakeAddress(),
            'billing_address' => null,
            'different_billing_address' => false,
            'shipping_rate_id' => $shippingRate->id,
            'shipping_carrier_id' => $shippingCarrier->id,
            'shipping_carrier_name' => $shippingCarrier->getTranslations('name'),
            'shipping_rate_name' => $shippingRate->getTranslations('name'),
            'payment_gateway_id' => $paymentGateway->id,
            'payment_gateway_name' => $paymentGateway->getTranslations('name'),
            'coupon_id' => null,
            'coupon_code' => null,
            'notes' => null,
            'items' => [],
            'total_quantity' => 0,
            'currency_code' => 'USD',
            'exchange_rate' => 1.0,
            'prices_include_tax' => Setting::getValue('prices_include_tax'),
            'shipping_is_taxable' => Setting::getValue('shipping_is_taxable'),
            'tax_based_on' => Setting::getValue('tax_based_on') ?? TaxBasedOn::Shipping->value,
            'default_tax_rate' => Setting::getValue('default_tax_rate'),
            'tax_store_country_code' => Setting::getValue('store_country_code'),
            'tax_store_state' => Setting::getValue('store_state'),
            'tax_store_postal_code' => Setting::getValue('store_postal_code'),
            'subtotal' => $subtotal,
            'tax_total' => $taxAmount,
            'shipping_total' => $shippingAmount,
            'discount_total' => $discountAmount,
            'total' => $total,
            'order_id' => null,
            'status' => CheckoutSessionStatus::Pending,
            'step' => CheckoutStep::PaymentInitiated,
            'expires_at' => now()->addMinutes(10),
            'completed_at' => null,
        ];
    }

    public function initiated(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => CheckoutSessionStatus::Pending,
            'step' => CheckoutStep::ContactInformation,
            'shipping_address' => null,
            'items' => null,
            'currency_code' => null,
            'prices_include_tax' => null,
            'shipping_is_taxable' => null,
            'tax_based_on' => null,
            'default_tax_rate' => null,
            'tax_store_country_code' => null,
            'tax_store_state' => null,
            'tax_store_postal_code' => null,
            'expires_at' => null,
            'subtotal' => 0,
            'tax_total' => 0,
            'shipping_total' => 0,
            'discount_total' => 0,
            'total' => 0,
            'shipping_rate_id' => null,
            'shipping_carrier_id' => null,
            'shipping_rate_name' => null,
            'shipping_carrier_name' => null,
            'payment_gateway_id' => null,
            'payment_gateway_name' => null,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => CheckoutSessionStatus::Pending,
            'expires_at' => now()->addMinutes(10),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => CheckoutSessionStatus::Completed,
            'completed_at' => now(),
        ]);
    }

    public function canceled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => CheckoutSessionStatus::Canceled,
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function fakeAddress(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'company' => fake()->optional()->company(),
            'address_line_1' => fake()->streetAddress(),
            'address_line_2' => fake()->optional()->secondaryAddress(),
            'city' => fake()->city(),
            'state' => fake()->state(),
            'postal_code' => fake()->postcode(),
            'country_code' => 'US',
            'phone' => fake()->phoneNumber(),
        ];
    }
}
