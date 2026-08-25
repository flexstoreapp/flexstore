<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\RefundStatus;
use App\Models\Order;
use App\Models\OrderRefund;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderRefund>
 */
#[UseModel(OrderRefund::class)]
final class OrderRefundFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'status' => fake()->randomElement(RefundStatus::cases()),
            'amount' => fake()->randomFloat(2, 10, 500),
            'is_manual_total' => false,
            'reason' => fake()->optional()->sentence(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => RefundStatus::Pending,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => RefundStatus::Completed,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => RefundStatus::Failed,
        ]);
    }

    public function totalOverridden(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_manual_total' => true,
        ]);
    }
}
