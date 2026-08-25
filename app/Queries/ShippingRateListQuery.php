<?php

declare(strict_types=1);

namespace App\Queries;

use App\Filters\Criteria\SortCriterion;
use App\Filters\Criteria\TextSearchCriterion;
use App\Filters\CriteriaCollection;
use App\Filters\FilterManager;
use App\Filters\Strategies\ShippingRateCarrierSortStrategy;
use App\Filters\Strategies\ShippingRateRegionSortStrategy;
use App\Filters\Strategies\TranslatableColumnSortStrategy;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ShippingRate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final readonly class ShippingRateListQuery
{
    /**
     * @param  FilterManager<ShippingRate>  $filterManager
     */
    public function __construct(private FilterManager $filterManager)
    {
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, ShippingRate>
     */
    public function execute(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        /** @var CriteriaCollection<ShippingRate> $criteria */
        $criteria = new CriteriaCollection();

        /** @var TextSearchCriterion<ShippingRate> $textSearch */
        $textSearch = new TextSearchCriterion(['name', 'region.name', 'carrier.name']);
        $criteria->add('query', $textSearch);

        /** @var SortCriterion<ShippingRate> $sortCriterion */
        $sortCriterion = new SortCriterion(
            allowedColumns: ['name', 'rate', 'region', 'carrier', 'is_active', 'created_at'],
            // @phpstan-ignore argument.type
            columnStrategies: [
                'name' => new TranslatableColumnSortStrategy('name'),
                'region' => new ShippingRateRegionSortStrategy(),
                'carrier' => new ShippingRateCarrierSortStrategy(),
            ],
            direction: $filters['direction'] ?? null,
        );
        $criteria->add('sort', $sortCriterion);

        $this->filterManager->setCriteria($criteria);

        $shippingRates = ShippingRate::query()
            ->with(['region:id,name', 'carrier:id,name'])
            ->tap(fn (Builder $query): Builder => $this->filterManager->apply($query, $filters))
            ->paginate($perPage)
            ->withQueryString();

        return $shippingRates->setCollection(
            $this->hydrateExcludedRelations($shippingRates->getCollection())
        );
    }

    /**
     * @param  Collection<int, ShippingRate>  $shippingRates
     * @return Collection<int, ShippingRate>
     */
    private function hydrateExcludedRelations(Collection $shippingRates): Collection
    {
        $excludedProducts = $this->getExcludedProducts($shippingRates);
        $excludedCategories = $this->getExcludedCategories($shippingRates);
        $excludedBrands = $this->getExcludedBrands($shippingRates);

        return $shippingRates->map(function (ShippingRate $shippingRate) use ($excludedProducts, $excludedCategories, $excludedBrands): ShippingRate {
            $shippingRate->fill([
                'excluded_products' => $excludedProducts->filter(fn (Product $product): bool => in_array($product->id, $shippingRate->excluded_products))->values(),
                'excluded_categories' => $excludedCategories->filter(fn (Category $category): bool => in_array($category->id, $shippingRate->excluded_categories))->values(),
                'excluded_brands' => $excludedBrands->filter(fn (Brand $brand): bool => in_array($brand->id, $shippingRate->excluded_brands))->values(),
            ]);

            return $shippingRate;
        });
    }

    /**
     * @param  Collection<int, ShippingRate>  $shippingRates
     * @return Collection<int, Product>
     */
    private function getExcludedProducts(Collection $shippingRates): Collection
    {
        $excludedProductIds = $shippingRates->pluck('excluded_products')->flatten()->unique();

        if ($excludedProductIds->isEmpty()) {
            return new Collection();
        }

        return Product::query()
            ->whereIn('id', $excludedProductIds)
            ->withFeaturedMedia()
            ->get(['id', 'type', 'title', 'price'])
            ->append('featured_media')
            ->makeHidden(['mediaGallery']);
    }

    /**
     * @param  Collection<int, ShippingRate>  $shippingRates
     * @return Collection<int, Category>
     */
    private function getExcludedCategories(Collection $shippingRates): Collection
    {
        $excludedCategoryIds = $shippingRates->pluck('excluded_categories')->flatten()->unique();

        if ($excludedCategoryIds->isEmpty()) {
            return new Collection();
        }

        return Category::query()->whereIn('id', $excludedCategoryIds)->get(['id', 'name']);
    }

    /**
     * @param  Collection<int, ShippingRate>  $shippingRates
     * @return Collection<int, Brand>
     */
    private function getExcludedBrands(Collection $shippingRates): Collection
    {
        $excludedBrandIds = $shippingRates->pluck('excluded_brands')->flatten()->unique();

        if ($excludedBrandIds->isEmpty()) {
            return new Collection();
        }

        return Brand::query()->whereIn('id', $excludedBrandIds)->get(['id', 'name']);
    }
}
