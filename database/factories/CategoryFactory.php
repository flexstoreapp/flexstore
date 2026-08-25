<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\Category>
 */
#[UseModel(Category::class)]
final class CategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $name = fake()->unique()->words(3, true),
            'url_handle' => Str::slug($name),
            'description' => fake()->paragraph(),
            'seo_title' => $name,
            'seo_description' => fake()->optional(0.7)->text(160),
            'is_active' => fake()->boolean(80),
            'sort_order' => 0,
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

    public function withParent(?Category $parent = null): self
    {
        return $this->state(function () use ($parent): array {
            $parentCategory = $parent ?? Category::factory()->create();
            $maxSortOrder = Category::query()
                ->where('parent_id', $parentCategory->id)
                ->max('sort_order') ?? -1;

            return [
                'parent_id' => $parentCategory->id,
                'sort_order' => $maxSortOrder + 1,
            ];
        });
    }
}
