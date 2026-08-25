<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ReviewStatus;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Review>
 */
#[UseModel(Review::class)]
final class ReviewFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'user_id' => User::factory(),
            'rating' => fake()->numberBetween(1, 5),
            'title' => fake()->optional(0.7)->sentence(),
            'content' => fake()->paragraph(),
            'status' => fake()->randomElement(ReviewStatus::cases()),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ReviewStatus::Pending,
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ReviewStatus::Approved,
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ReviewStatus::Rejected,
        ]);
    }
}
