<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\FulfillmentStatus;
use App\Enums\PaymentStatus;
use App\Enums\Role;
use App\Enums\TaxBasedOn;
use App\Models\Order;
use App\Models\PaymentGateway;
use App\Models\Setting;
use App\Models\ShippingCarrier;
use App\Models\ShippingRate;
use App\Models\User;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
#[UseModel(Order::class)]
final class OrderFactory extends Factory
{
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 10, 500);
        $taxAmount = round($subtotal * 0.1, 2);
        $shippingAmount = fake()->randomFloat(2, 0, 25);
        $discountAmount = fake()->randomFloat(2, 0, $subtotal * 0.2);
        $totalAmount = round($subtotal + $taxAmount + $shippingAmount - $discountAmount, 2);
        $shippingCarrier = ShippingCarrier::query()->inRandomOrder()->first()
            ?? ShippingCarrier::factory()->create();
        $shippingRate = ShippingRate::query()->with('region')->inRandomOrder()->first()
            ?? ShippingRate::factory()->create();
        $paymentGateway = PaymentGateway::query()->inRandomOrder()->first()
            ?? PaymentGateway::factory()->create();
        $paymentStatus = fake()->randomElement(PaymentStatus::cases());

        $customer = User::query()->inRandomOrder()->whereRelation('roles', 'name', Role::Customer)->first()
            ?? User::factory()->customer()->create();

        return [
            'payment_status' => $paymentStatus,
            'fulfillment_status' => fake()->randomElement(FulfillmentStatus::cases()),
            'customer_id' => $customer->id,
            'customer_email' => fake()->email(),
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
            'total' => $totalAmount,
            'refund_total' => 0,
            'currency_code' => 'USD',
            'exchange_rate' => 1.0,
            'shipping_carrier_id' => $shippingCarrier->id,
            'shipping_carrier_name' => $shippingCarrier->getTranslations('name'),
            'shipping_rate_id' => $shippingRate->id,
            'shipping_rate_name' => $shippingRate->getTranslations('name'),
            'region_id' => $shippingRate->region_id,
            'region_name' => $shippingRate->region?->getTranslations('name') ?: null,
            'payment_gateway_id' => $paymentGateway->id,
            'payment_gateway_name' => $paymentGateway->getTranslations('name'),
            'notes' => fake()->optional()->paragraph(),
        ];
    }

    #[Override]
    public function configure(): static
    {
        return $this->afterCreating(function (Order $order): void {
            if ($order->paid_total !== null && $order->paid_total !== '0.0000') {
                return;
            }

            $isPaid = in_array($order->payment_status, [
                PaymentStatus::Paid, PaymentStatus::PartiallyPaid, PaymentStatus::PartiallyRefunded, PaymentStatus::Refunded,
            ], true);

            if ($isPaid) {
                $order->update([
                    'paid_total' => $order->total,
                    'net_paid_total' => BigDecimal::of($order->total)->minus($order->refund_total ?? '0')->toScale(4, RoundingMode::HalfUp)->toString(),
                    'balance_due_total' => '0.0000',
                    'credit_due_total' => '0.0000',
                ]);
            } else {
                $order->update([
                    'paid_total' => '0.0000',
                    'net_paid_total' => '0.0000',
                    'balance_due_total' => $order->total,
                    'credit_due_total' => '0.0000',
                ]);
            }
        });
    }

    public function paid(): static
    {
        return $this->state(fn (): array => [
            'payment_status' => PaymentStatus::Paid,
        ]);
    }

    public function unpaid(): static
    {
        return $this->state(fn (): array => [
            'payment_status' => PaymentStatus::Unpaid,
        ]);
    }

    public function unfulfilled(): static
    {
        return $this->state(fn (): array => [
            'payment_status' => PaymentStatus::Unpaid,
            'fulfillment_status' => FulfillmentStatus::Unfulfilled,
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn (): array => [
            'payment_status' => PaymentStatus::Paid,
            'fulfillment_status' => FulfillmentStatus::InProgress,
        ]);
    }

    public function fulfilled(): static
    {
        return $this->state(fn (): array => [
            'payment_status' => PaymentStatus::Paid,
            'fulfillment_status' => FulfillmentStatus::Fulfilled,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (): array => [
            'payment_status' => PaymentStatus::Failed,
            'fulfillment_status' => FulfillmentStatus::Unfulfilled,
        ]);
    }

    public function canceled(): static
    {
        return $this->state(fn (): array => [
            'payment_status' => PaymentStatus::Canceled,
            'canceled_at' => now(),
        ]);
    }
}
