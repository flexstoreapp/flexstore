<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\RefundItemType;
use App\Models\OrderItem;
use App\Models\OrderRefund;
use App\Models\OrderRefundItem;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderRefundItem>
 */
#[UseModel(OrderRefundItem::class)]
final class OrderRefundItemFactory extends Factory
{
    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 5);
        $unitPrice = BigDecimal::of((string) fake()->randomFloat(2, 5, 50));
        $amount = $unitPrice->multipliedBy($quantity)->toScale(2, RoundingMode::HalfUp);

        return [
            'order_refund_id' => OrderRefund::factory(),
            'type' => RefundItemType::Product,
            'order_item_id' => OrderItem::factory(),
            'quantity' => $quantity,
            'amount' => $amount->toString(),
            'restock' => true,
        ];
    }

    public function tax(): static
    {
        return $this->state(fn (): array => [
            'type' => RefundItemType::Tax,
            'order_item_id' => null,
            'quantity' => null,
            'amount' => fake()->randomFloat(2, 1, 20),
            'restock' => false,
        ]);
    }

    public function discount(): static
    {
        return $this->state(fn (): array => [
            'type' => RefundItemType::Discount,
            'order_item_id' => null,
            'quantity' => null,
            'amount' => fake()->randomFloat(2, 1, 20),
            'restock' => false,
        ]);
    }

    public function shipping(): static
    {
        return $this->state(fn (): array => [
            'type' => RefundItemType::Shipping,
            'order_item_id' => null,
            'quantity' => null,
            'restock' => false,
        ]);
    }

    public function adjustment(): static
    {
        return $this->state(fn (): array => [
            'type' => RefundItemType::Adjustment,
            'order_item_id' => null,
            'quantity' => null,
            'restock' => false,
        ]);
    }

    public function noRestock(): static
    {
        return $this->state(fn (): array => [
            'restock' => false,
        ]);
    }
}
