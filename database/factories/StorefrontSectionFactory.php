<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ProductSource;
use App\Enums\StorefrontSectionType;
use App\Models\StorefrontSection;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StorefrontSection>
 */
#[UseModel(StorefrontSection::class)]
final class StorefrontSectionFactory extends Factory
{
    public function definition(): array
    {
        $type = fake()->randomElement(StorefrontSectionType::cases());

        return [
            'type' => $type,
            'title' => fake()->words(3, true),
            'settings' => $this->getDefaultSettingsForType($type),
            'sort_order' => fake()->numberBetween(0, 100),
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

    public function ofType(StorefrontSectionType $type): self
    {
        return $this->state(fn (array $attributes): array => [
            'type' => $type,
            'settings' => $this->getDefaultSettingsForType($type),
        ]);
    }

    public function featuredProducts(): self
    {
        return $this->ofType(StorefrontSectionType::FeaturedProducts);
    }

    public function productCarousel(): self
    {
        return $this->ofType(StorefrontSectionType::ProductCarousel);
    }

    /**
     * @return array<string, mixed>
     */
    private function getDefaultSettingsForType(StorefrontSectionType $type): array
    {
        return match ($type) {
            StorefrontSectionType::HeroSlider => [
                'slides' => [],
                'side_tiles' => [],
                'autoplay' => true,
                'autoplay_speed' => 6000,
                'transition' => 'fade',
                'show_dots' => true,
            ],
            StorefrontSectionType::InfoStrip => [
                'items' => [],
            ],
            StorefrontSectionType::CategoryGrid => [
                'categories' => [],
            ],
            StorefrontSectionType::FeaturedProducts => [
                'product_source' => ProductSource::Latest->value,
                'product_limit' => 8,
                'category_id' => null,
                'product_ids' => null,
            ],
            StorefrontSectionType::ProductTabs => [
                'tabs' => [],
            ],
            StorefrontSectionType::PromoBanners => [
                'banners' => [],
            ],
            StorefrontSectionType::ProductCarousel => [
                'product_source' => ProductSource::Latest->value,
                'product_limit' => 10,
                'category_id' => null,
                'product_ids' => null,
            ],
            StorefrontSectionType::ProductColumns => [
                'columns' => [],
            ],
            StorefrontSectionType::BrandStrip => [
                'brand_ids' => [],
                'grayscale' => false,
            ],
            StorefrontSectionType::Testimonials => [
                'testimonials' => [],
            ],
        };
    }
}
