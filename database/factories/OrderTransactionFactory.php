<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Order;
use App\Models\OrderTransaction;
use App\Models\PaymentGateway;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderTransaction>
 */
#[UseModel(OrderTransaction::class)]
final class OrderTransactionFactory extends Factory
{
    public function definition(): array
    {
        $gateway = PaymentGateway::query()->inRandomOrder()->first()
            ?? PaymentGateway::factory()->create();

        return [
            'order_id' => Order::factory(),
            'type' => TransactionType::Sale,
            'status' => TransactionStatus::Success,
            'amount' => fake()->randomFloat(2, 10, 500),
            'currency_code' => 'USD',
            'payment_gateway_id' => $gateway->id,
            'gateway_reference' => fake()->uuid(),
        ];
    }

    public function sale(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => TransactionType::Sale,
        ]);
    }

    public function refund(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => TransactionType::Refund,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => TransactionStatus::Pending,
        ]);
    }

    public function successful(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => TransactionStatus::Success,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => TransactionStatus::Failed,
            'failure_reason' => fake()->randomElement([
                'Insufficient funds',
                'Card expired',
                'Card declined',
                'Payment gateway error',
            ]),
        ]);
    }
}
