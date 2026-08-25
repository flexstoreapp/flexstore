<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PaymentSessionPurpose;
use App\Enums\PaymentSessionStatus;
use App\Models\CheckoutSession;
use App\Models\PaymentGateway;
use App\Models\PaymentSession;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentSession>
 */
#[UseModel(PaymentSession::class)]
final class PaymentSessionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'payment_gateway_id' => PaymentGateway::factory(),
            'owner_type' => CheckoutSession::class,
            'owner_id' => CheckoutSession::factory(),
            'purpose' => PaymentSessionPurpose::Checkout,
            'currency_code' => 'USD',
            'amount' => fake()->randomFloat(2, 10, 500),
            'customer_email' => fake()->email(),
            'status' => PaymentSessionStatus::Pending,
            'return_url' => fake()->url(),
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PaymentSessionStatus::Completed,
            'completed_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PaymentSessionStatus::Failed,
        ]);
    }

    public function checkout(): static
    {
        return $this->state(fn (array $attributes): array => [
            'purpose' => PaymentSessionPurpose::Checkout,
        ]);
    }

    public function paymentRequest(): static
    {
        return $this->state(fn (array $attributes): array => [
            'purpose' => PaymentSessionPurpose::PaymentRequest,
        ]);
    }
}
