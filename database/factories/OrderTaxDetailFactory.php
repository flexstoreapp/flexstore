<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\OrderTaxDetailItemType;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderTaxDetail;
use App\Models\TaxRate;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderTaxDetail>
 */
#[UseModel(OrderTaxDetail::class)]
final class OrderTaxDetailFactory extends Factory
{
    public function definition(): array
    {
        $taxableAmount = fake()->randomFloat(2, 10, 500);
        $taxRate = fake()->randomFloat(2, 5, 15); // 5% to 15%
        $taxAmount = round($taxableAmount * ($taxRate / 100), 2);

        return [
            'order_id' => Order::factory(),
            'order_item_id' => fake()->boolean(80) ? OrderItem::factory() : null,
            'tax_rate_id' => TaxRate::factory(),
            'item_type' => OrderTaxDetailItemType::Product,
            'tax_name' => fake()->randomElement(['VAT', 'GST', 'PST', 'HST']),
            'tax_rate' => $taxRate,
            'taxable_amount' => $taxableAmount,
            'tax_amount' => $taxAmount,
            'is_compound' => fake()->boolean(20),
        ];
    }

    public function forOrder(Order $order): static
    {
        return $this->state(fn (array $attributes): array => [
            'order_id' => $order->id,
        ]);
    }

    public function forOrderItem(OrderItem $orderItem): static
    {
        return $this->state(fn (array $attributes): array => [
            'order_id' => $orderItem->order_id,
            'order_item_id' => $orderItem->id,
        ]);
    }

    public function forTaxRate(TaxRate $taxRate): static
    {
        return $this->state(fn (array $attributes): array => [
            'tax_rate_id' => $taxRate->id,
            'tax_name' => $taxRate->getTranslations('name'),
            'tax_rate' => $taxRate->rate,
        ]);
    }
}
