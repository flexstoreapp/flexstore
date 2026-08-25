<?php

declare(strict_types=1);

namespace App\Enums;

enum StorefrontSectionType: string
{
    case HeroSlider = 'hero_slider';
    case InfoStrip = 'info_strip';
    case CategoryGrid = 'category_grid';
    case FeaturedProducts = 'featured_products';
    case ProductTabs = 'product_tabs';
    case PromoBanners = 'promo_banners';
    case ProductCarousel = 'product_carousel';
    case ProductColumns = 'product_columns';
    case BrandStrip = 'brand_strip';
    case Testimonials = 'testimonials';

    /**
     * @return array{fields: list<string>, lists: array<string, list<string>>}
     */
    public function mediaFields(): array
    {
        return match ($this) {
            self::HeroSlider => ['fields' => [], 'lists' => ['slides' => ['image'], 'side_tiles' => ['image']]],
            self::CategoryGrid => ['fields' => [], 'lists' => ['categories' => ['image']]],
            self::PromoBanners => ['fields' => [], 'lists' => ['banners' => ['image']]],
            default => ['fields' => [], 'lists' => []],
        };
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return list<int>
     */
    public function extractMediaIds(array $settings): array
    {
        $map = $this->mediaFields();
        $ids = [];

        foreach ($map['fields'] as $field) {
            if (is_numeric($settings[$field] ?? null)) {
                $ids[] = (int) $settings[$field];
            }
        }

        foreach ($map['lists'] as $listKey => $imageKeys) {
            $list = $settings[$listKey] ?? null;

            if (! is_array($list)) {
                continue;
            }

            foreach ($list as $item) {
                if (! is_array($item)) {
                    continue;
                }

                foreach ($imageKeys as $imageKey) {
                    if (is_numeric($item[$imageKey] ?? null)) {
                        $ids[] = (int) $item[$imageKey];
                    }
                }
            }
        }

        return array_values(array_unique($ids));
    }
}
