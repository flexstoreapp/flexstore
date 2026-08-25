<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\StockMovementReason;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockMovement>
 */
#[UseModel(StockMovement::class)]
final class StockMovementFactory extends Factory
{
    public function definition(): array
    {
        $quantityBefore = fake()->numberBetween(0, 100);
        $quantity = fake()->numberBetween(-20, 50);
        $quantityAfter = max(0, $quantityBefore + $quantity);

        return [
            'product_id' => Product::factory(),
            'product_variant_id' => null,
            'user_id' => User::factory(),
            'quantity' => $quantity,
            'quantity_before' => $quantityBefore,
            'quantity_after' => $quantityAfter,
            'reason' => fake()->randomElement(StockMovementReason::cases()),
            'reference_type' => null,
            'reference_id' => null,
            'notes' => fake()->optional()->sentence(),
        ];
    }

    public function forProduct(Product $product): static
    {
        return $this->state(fn (array $attributes): array => [
            'product_id' => $product->id,
        ]);
    }

    public function forVariant(ProductVariant $variant): static
    {
        return $this->state(fn (array $attributes): array => [
            'product_id' => $variant->product_id,
            'product_variant_id' => $variant->id,
        ]);
    }

    public function byUser(User $user): static
    {
        return $this->state(fn (array $attributes): array => [
            'user_id' => $user->id,
        ]);
    }

    public function withReason(StockMovementReason $reason): static
    {
        return $this->state(fn (array $attributes): array => [
            'reason' => $reason,
        ]);
    }

    public function manual(): static
    {
        return $this->withReason(StockMovementReason::Manual);
    }

    public function sale(): static
    {
        return $this->withReason(StockMovementReason::Sale);
    }

    public function refund(): static
    {
        return $this->withReason(StockMovementReason::Refund);
    }

    public function received(): static
    {
        return $this->withReason(StockMovementReason::Received);
    }

    public function damaged(): static
    {
        return $this->withReason(StockMovementReason::Damaged);
    }

    public function adjustment(int $quantityBefore, int $quantityChange): static
    {
        return $this->state(fn (array $attributes): array => [
            'quantity_before' => $quantityBefore,
            'quantity' => $quantityChange,
            'quantity_after' => max(0, $quantityBefore + $quantityChange),
        ]);
    }
}
