<?php

declare(strict_types=1);

namespace App\Queries;

use App\Enums\ReviewStatus;
use App\Filters\Criteria\TextSearchCriterion;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Review;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final readonly class StorefrontShopFacetsQuery
{
    private const array RATING_THRESHOLDS = [4.5, 4.0, 3.0];

    /**
     * @return array{categories: list<array<string, mixed>>|null, brands: list<array<string, mixed>>|null, price_buckets: list<array{min: string|null, max: string|null}>, rating_buckets: list<float>}
     */
    public function execute(
        ?int $contextCategoryId = null,
        ?int $contextBrandId = null,
        ?string $search = null,
        bool $includeCategories = true,
        bool $includeBrands = true,
    ): array {
        $scope = function (Builder $query) use ($contextCategoryId, $contextBrandId, $search): void {
            $query->where('is_active', true);

            if ($contextCategoryId !== null) {
                $query->whereIn('category_id', Category::query()->withDescendants($contextCategoryId)->pluck('id'));
            }

            if ($contextBrandId !== null) {
                $query->where('brand_id', $contextBrandId);
            }

            if ($search !== null && mb_trim($search) !== '') {
                /** @var TextSearchCriterion<Product> $textSearch */
                $textSearch = new TextSearchCriterion(['title', 'description', 'sku']);
                $textSearch->apply($query, $search);
            }
        };

        return [
            'categories' => $includeCategories ? $this->categories($scope) : null,
            'brands' => $includeBrands ? $this->brands($scope) : null,
            'price_buckets' => $this->priceBuckets($scope),
            'rating_buckets' => $this->ratingBuckets($scope),
        ];
    }

    /**
     * @param  Closure(Builder<Product>): void  $scope
     * @return Builder<Product>
     */
    private function scopedProducts(Closure $scope): Builder
    {
        $query = Product::query();
        $scope($query);

        return $query;
    }

    /**
     * @param  Closure(Builder<Product>): void  $scope
     * @return list<array<string, mixed>>
     */
    private function categories(Closure $scope): array
    {
        $categories = Category::query()->get(['id', 'parent_id', 'sort_order', 'name', 'url_handle', 'is_active']);

        /** @var Collection<int, int> $directCounts */
        $directCounts = $this->scopedProducts($scope)
            ->whereNotNull('category_id')
            ->selectRaw('category_id, count(*) as aggregate')
            ->groupBy('category_id')
            ->pluck('aggregate', 'category_id');

        /** @var Collection<int|string, Collection<int, Category>> $childrenByParent */
        $childrenByParent = $categories->groupBy('parent_id');

        $countWithDescendants = function (int $id) use (&$countWithDescendants, $childrenByParent, $directCounts): int {
            $total = (int) $directCounts->get($id, 0);

            foreach ($childrenByParent->get($id, new Collection()) as $child) {
                $total += $countWithDescendants($child->id);
            }

            return $total;
        };

        /** @var list<array<string, mixed>> $facets */
        $facets = $categories
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->sortBy('sort_order')
            ->map(fn (Category $category): array => [
                'id' => $category->id,
                'name' => $category->getTranslations('name'),
                'url_handle' => $category->url_handle,
                'count' => $countWithDescendants($category->id),
            ])
            ->filter(fn (array $facet): bool => $facet['count'] > 0)
            ->values()
            ->all();

        return $facets;
    }

    /**
     * @param  Closure(Builder<Product>): void  $scope
     * @return list<array<string, mixed>>
     */
    private function brands(Closure $scope): array
    {
        /** @var Collection<int, int> $counts */
        $counts = $this->scopedProducts($scope)
            ->whereNotNull('brand_id')
            ->selectRaw('brand_id, count(*) as aggregate')
            ->groupBy('brand_id')
            ->pluck('aggregate', 'brand_id');

        if ($counts->isEmpty()) {
            return [];
        }

        /** @var list<array<string, mixed>> $facets */
        $facets = Brand::query()
            ->where('is_active', true)
            ->whereIn('id', $counts->keys())
            ->orderBy('name')
            ->get(['id', 'name', 'url_handle'])
            ->map(fn (Brand $brand): array => [
                'id' => $brand->id,
                'name' => $brand->getTranslations('name'),
                'url_handle' => $brand->url_handle,
                'count' => (int) $counts->get($brand->id, 0),
            ])
            ->values()
            ->all();

        return $facets;
    }

    /**
     * @param  Closure(Builder<Product>): void  $scope
     * @return list<array{min: string|null, max: string|null}>
     */
    private function priceBuckets(Closure $scope): array
    {
        $simpleRange = $this->scopedProducts($scope)
            ->whereDoesntHave('variants')
            ->selectRaw('min(price) as min_price, max(price) as max_price')
            ->first();

        $variantRange = ProductVariant::query()
            ->whereHas('product', $scope)
            ->selectRaw('min(price) as min_price, max(price) as max_price')
            ->first();

        $prices = array_map(floatval(...), array_filter([
            $simpleRange?->getAttribute('min_price'),
            $simpleRange?->getAttribute('max_price'),
            $variantRange?->getAttribute('min_price'),
            $variantRange?->getAttribute('max_price'),
        ], fn (mixed $price): bool => $price !== null));

        $min = $prices === [] ? 0.0 : min($prices);
        $max = $prices === [] ? 0.0 : max($prices);

        if ($max <= 0.0 || $max === $min) {
            return [];
        }

        $step = $this->niceNumber($max / 4);

        if ($step <= 0.0) {
            return [];
        }

        return [
            ['min' => null, 'max' => $this->formatBoundary($step)],
            ['min' => $this->formatBoundary($step), 'max' => $this->formatBoundary($step * 2)],
            ['min' => $this->formatBoundary($step * 2), 'max' => $this->formatBoundary($step * 3)],
            ['min' => $this->formatBoundary($step * 3), 'max' => null],
        ];
    }

    /**
     * @param  Closure(Builder<Product>): void  $scope
     * @return list<float>
     */
    private function ratingBuckets(Closure $scope): array
    {
        $maxAverage = (float) Review::query()
            ->where('status', ReviewStatus::Approved->value)
            ->whereIn('product_id', $this->scopedProducts($scope)->select('id'))
            ->groupBy('product_id')
            ->selectRaw('avg(rating) as average_rating')
            ->orderByDesc('average_rating')
            ->value('average_rating');

        return array_values(array_filter(
            self::RATING_THRESHOLDS,
            fn (float $threshold): bool => $threshold <= $maxAverage,
        ));
    }

    private function niceNumber(float $value): float
    {
        if ($value <= 0.0) {
            return 0.0;
        }

        $magnitude = 10 ** floor(log10($value));
        $fraction = $value / $magnitude;

        $nice = match (true) {
            $fraction <= 1.0 => 1.0,
            $fraction <= 2.0 => 2.0,
            $fraction <= 2.5 => 2.5,
            $fraction <= 5.0 => 5.0,
            default => 10.0,
        };

        return $nice * $magnitude;
    }

    private function formatBoundary(float $value): string
    {
        return mb_rtrim(mb_rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }
}
