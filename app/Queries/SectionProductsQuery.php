<?php

declare(strict_types=1);

namespace App\Queries;

use App\DTOs\ProductCard;
use App\Enums\ProductSource;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final readonly class SectionProductsQuery
{
    /**
     * @param  array<string, mixed>  $settings
     * @return list<array<string, mixed>>
     */
    public function execute(array $settings): array
    {
        $source = ProductSource::tryFrom((string) ($settings['product_source'] ?? ProductSource::Latest->value))
            ?? ProductSource::Latest;
        $limit = (int) ($settings['product_limit'] ?? 8);
        $limit = max(1, min($limit, 50));

        $products = match ($source) {
            ProductSource::Featured => $this->byIds($settings['product_ids'] ?? []),
            ProductSource::Category => $this->byCategory($settings['category_id'] ?? null, $limit),
            ProductSource::Brand => $this->byBrand($settings['brand_id'] ?? null, $limit),
            ProductSource::Latest => $this->latest($limit),
        };

        /** @var list<array<string, mixed>> $cards */
        $cards = $products->map(fn (Product $product): array => $this->toCard($product))->values()->all();

        return $cards;
    }

    /**
     * @return Collection<int, Product>
     */
    private function byIds(mixed $productIds): Collection
    {
        if (! is_array($productIds) || $productIds === []) {
            return collect();
        }

        $ids = array_values(array_filter(array_map(intval(...), $productIds)));

        $products = $this->baseQuery()
            ->whereIn('id', $ids)
            ->where('is_active', true)
            ->get()
            ->keyBy('id');

        return collect($ids)
            ->map(fn (int $id): ?Product => $products->get($id))
            ->filter()
            ->values();
    }

    /**
     * @return Collection<int, Product>
     */
    private function byCategory(mixed $categoryId, int $limit): Collection
    {
        if (! is_numeric($categoryId)) {
            return collect();
        }

        $categoryIds = Category::query()->withDescendants((int) $categoryId)->pluck('id');

        return $this->baseQuery()
            ->where('is_active', true)
            ->whereIn('category_id', $categoryIds)
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, Product>
     */
    private function byBrand(mixed $brandId, int $limit): Collection
    {
        if (! is_numeric($brandId)) {
            return collect();
        }

        return $this->baseQuery()
            ->where('is_active', true)
            ->where('brand_id', (int) $brandId)
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, Product>
     */
    private function latest(int $limit): Collection
    {
        return $this->baseQuery()
            ->where('is_active', true)
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Builder<Product>
     */
    private function baseQuery(): Builder
    {
        return Product::query()
            ->select(['id', 'url_handle', 'title', 'price', 'compare_at_price', 'in_stock', 'brand_id', 'category_id', 'created_at'])
            ->with([
                'brand:id,name,url_handle',
                'variants:id,product_id,price,compare_at_price,in_stock',
            ])
            ->withFeaturedMedia()
            ->withCount('variants');
    }

    /**
     * @return array<string, mixed>
     */
    private function toCard(Product $product): array
    {
        return ProductCard::fromProduct($product)->toArray();
    }
}
