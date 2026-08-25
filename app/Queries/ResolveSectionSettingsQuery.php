<?php

declare(strict_types=1);

namespace App\Queries;

use App\Enums\StorefrontSectionType;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Media;
use App\Models\Product;
use App\Models\StorefrontSection;
use Illuminate\Support\Collection;

final readonly class ResolveSectionSettingsQuery
{
    public function __construct(
        private ResolveSectionMediaQuery $resolveSectionMediaQuery,
    ) {
    }

    /**
     * @param  Collection<int, Media>  $preloadedMedia
     * @return array<string, mixed>
     */
    public function execute(StorefrontSection $section, Collection $preloadedMedia): array
    {
        $settings = $section->settings ?? [];

        $settings = match ($section->type) {
            StorefrontSectionType::CategoryGrid => $this->resolveCategoriesSettings($settings),
            StorefrontSectionType::FeaturedProducts,
            StorefrontSectionType::ProductCarousel => $this->resolveProductSettings($settings),
            StorefrontSectionType::ProductTabs => $this->resolveGroupedProductSettings($settings, 'tabs'),
            StorefrontSectionType::ProductColumns => $this->resolveGroupedProductSettings($settings, 'columns'),
            StorefrontSectionType::BrandStrip => $this->resolveBrandsSettings($settings),
            default => $settings,
        };

        return $this->resolveSectionMediaQuery->execute($section->type, $settings, $preloadedMedia);
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    private function resolveProductSettings(array $settings): array
    {
        if (isset($settings['brand_id'])) {
            $brand = Brand::query()
                ->where('id', $settings['brand_id'])
                ->first(['id', 'name']);

            if ($brand) {
                $settings['brand'] = [
                    'id' => $brand->id,
                    'name' => $brand->getTranslations('name'),
                ];
            }
        }

        if (isset($settings['category_id'])) {
            $category = Category::query()
                ->where('id', $settings['category_id'])
                ->first(['id', 'name']);

            if ($category) {
                $settings['category'] = [
                    'id' => $category->id,
                    'name' => $category->getTranslations('name'),
                ];
            }
        }

        if (isset($settings['product_ids']) && is_array($settings['product_ids']) && count($settings['product_ids']) > 0) {
            $settings['products'] = $this->resolveProducts(array_values(array_map(intval(...), $settings['product_ids'])));
        }

        return $settings;
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    private function resolveGroupedProductSettings(array $settings, string $groupKey): array
    {
        if (! isset($settings[$groupKey]) || ! is_array($settings[$groupKey])) {
            return $settings;
        }

        foreach ($settings[$groupKey] as &$group) {
            if (is_array($group)) {
                $group = $this->resolveProductSettings($group);
            }
        }

        unset($group);

        return $settings;
    }

    /**
     * @param  list<int>  $productIds
     * @return list<array<string, mixed>>
     */
    private function resolveProducts(array $productIds): array
    {
        $products = Product::query()
            ->withFeaturedMedia()
            ->whereIn('id', $productIds)
            ->get(['id', 'type', 'title', 'price'])
            ->keyBy('id');

        /** @var list<array<string, mixed>> $resolved */
        $resolved = collect($productIds)
            ->map(function (int $productId) use ($products): ?array {
                $product = $products->get($productId);
                if (! $product) {
                    return null;
                }

                return [
                    'id' => $product->id,
                    'type' => $product->type->value,
                    'title' => $product->getTranslations('title'),
                    'price' => $product->price,
                    'price_range' => null,
                    'featured_media' => $product->featured_media,
                ];
            })
            ->filter()
            ->values()
            ->all();

        return $resolved;
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    private function resolveCategoriesSettings(array $settings): array
    {
        if (
            ! isset($settings['categories']) ||
            ! is_array($settings['categories']) ||
            count($settings['categories']) === 0
        ) {
            return $settings;
        }

        $categoryIds = array_column($settings['categories'], 'category_id');

        $categories = Category::query()
            ->whereIn('id', $categoryIds)
            ->get(['id', 'name', 'url_handle'])
            ->keyBy('id');

        foreach ($settings['categories'] as &$category) {
            $categoryModel = $categories->get($category['category_id']);
            if ($categoryModel) {
                $category['name'] = $categoryModel->getTranslations('name');
                $category['url_handle'] = $categoryModel->url_handle;
            }
        }

        unset($category);

        return $settings;
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    private function resolveBrandsSettings(array $settings): array
    {
        if (
            ! isset($settings['brand_ids']) ||
            ! is_array($settings['brand_ids']) ||
            count($settings['brand_ids']) === 0
        ) {
            return $settings;
        }

        $brands = Brand::query()
            ->with(['image:' . Media::displaySelect()])
            ->whereIn('id', $settings['brand_ids'])
            ->get(['id', 'name', 'url_handle', 'image_id'])
            ->keyBy('id');

        $settings['brands'] = collect($settings['brand_ids'])
            ->map(function (int $brandId) use ($brands): ?array {
                $brand = $brands->get($brandId);
                if (! $brand) {
                    return null;
                }

                return [
                    'id' => $brand->id,
                    'name' => $brand->getTranslations('name'),
                    'url_handle' => $brand->url_handle,
                    'image' => $brand->image,
                ];
            })
            ->filter()
            ->values()
            ->all();

        return $settings;
    }
}
