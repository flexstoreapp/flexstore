<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\MenuItemLinkType;
use App\Enums\MenuLocation;
use App\Models\Brand;
use App\Models\MenuItem;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\MenuItem>
 */
#[UseModel(MenuItem::class)]
final class MenuItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'location' => fake()->randomElement(MenuLocation::cases()),
            'label' => ['en' => fake()->words(2, true)],
            'link_type' => MenuItemLinkType::Custom,
            'url' => fake()->url(),
            'target' => '_self',
            'sort_order' => fake()->numberBetween(0, 100),
            'is_mega_menu' => false,
            'is_active' => fake()->boolean(80),
        ];
    }

    public function active(): self
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => true,
        ]);
    }

    public function inactive(): self
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    public function header(): self
    {
        return $this->state(fn (array $attributes): array => [
            'location' => MenuLocation::Header,
        ]);
    }

    public function footer(): self
    {
        return $this->state(fn (array $attributes): array => [
            'location' => MenuLocation::Footer,
        ]);
    }

    public function brand(?Brand $brand = null): self
    {
        return $this->state(fn (array $attributes): array => [
            'link_type' => MenuItemLinkType::Brand,
            'brand_id' => $brand?->id ?? Brand::factory(),
            'url' => null,
        ]);
    }

    public function megaMenu(): self
    {
        return $this->state(fn (array $attributes): array => [
            'is_mega_menu' => true,
        ]);
    }

    public function featured(): self
    {
        return $this->state(fn (array $attributes): array => [
            'is_mega_menu' => true,
            'featured_title' => ['en' => fake()->sentence(4)],
            'featured_url' => fake()->url(),
        ]);
    }

    public function withParent(?MenuItem $parent = null): self
    {
        return $this->state(fn (array $attributes): array => [
            'parent_id' => $parent?->id ?? MenuItem::factory()->create()->id,
        ]);
    }
}
