<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\CheckoutSession;
use App\Models\Product;
use App\Models\StockReservation;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockReservation>
 */
#[UseModel(StockReservation::class)]
final class StockReservationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'checkout_session_id' => CheckoutSession::factory(),
            'product_id' => Product::factory(),
            'product_variant_id' => null,
            'quantity' => fake()->numberBetween(1, 5),
            'expires_at' => now()->addMinutes(10),
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes): array => [
            'expires_at' => now()->subMinutes(10),
        ]);
    }
}
