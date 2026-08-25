<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Brand;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\Brand>
 */
#[UseModel(Brand::class)]
final class BrandFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $name = fake()->unique()->company(),
            'url_handle' => Str::slug($name),
            'description' => fake()->optional(0.7)->paragraph(),
            'seo_title' => $name,
            'seo_description' => fake()->optional(0.7)->text(160),
            'is_active' => fake()->boolean(80),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
