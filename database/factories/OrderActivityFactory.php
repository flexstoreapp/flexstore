<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\FulfillmentStatus;
use App\Enums\OrderActivityType;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderActivity;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderActivity>
 */
#[UseModel(OrderActivity::class)]
final class OrderActivityFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'user_id' => User::factory(),
            'type' => OrderActivityType::OrderPlaced,
            'comment' => null,
            'metadata' => null,
        ];
    }

    public function forOrder(Order $order): static
    {
        return $this->state(fn (array $attributes): array => [
            'order_id' => $order->id,
        ]);
    }

    public function fulfillmentStatusChange(?FulfillmentStatus $from = null, FulfillmentStatus $to = FulfillmentStatus::InProgress): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => OrderActivityType::FulfillmentStatusChanged,
            'metadata' => [
                'from_status' => $from?->value,
                'to_status' => $to->value,
            ],
        ]);
    }

    public function paymentStatusChange(PaymentStatus $from = PaymentStatus::Unpaid, PaymentStatus $to = PaymentStatus::Paid): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => OrderActivityType::PaymentStatusChanged,
            'metadata' => [
                'from_status' => $from->value,
                'to_status' => $to->value,
            ],
        ]);
    }

    public function note(string $comment = 'Test note'): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => OrderActivityType::NoteAdded,
            'comment' => $comment,
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $changes
     */
    public function orderEdited(array $changes = []): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => OrderActivityType::OrderEdited,
            'metadata' => ['changes' => $changes],
        ]);
    }
}
