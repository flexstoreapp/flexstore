<?php

declare(strict_types=1);

namespace App\Queries;

use App\Filters\Criteria\InStockCriterion;
use App\Filters\Criteria\LowStockCriterion;
use App\Filters\Criteria\TextSearchCriterion;
use App\Filters\CriteriaCollection;
use App\Filters\FilterManager;
use App\Models\Media;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Utilities\InputConverter;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Pagination\LengthAwarePaginator;

final readonly class InventoryListQuery
{
    /**
     * @param  FilterManager<Product>  $filterManager
     */
    public function __construct(private FilterManager $filterManager)
    {
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Product>
     */
    public function execute(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        /** @var CriteriaCollection<Product> $criteria */
        $criteria = new CriteriaCollection();

        /** @var TextSearchCriterion<Product> $querySearch */
        $querySearch = new TextSearchCriterion(['title', 'sku', 'url_handle']);
        $criteria->add('query', $querySearch);

        /** @var LowStockCriterion<Product> $lowStock */
        $lowStock = new LowStockCriterion();
        $criteria->add('low_stock', $lowStock);

        /** @var InStockCriterion<Product> $inStock */
        $inStock = new InStockCriterion();
        $criteria->add('in_stock', $inStock);

        $this->filterManager->setCriteria($criteria);
        $inStockFilterValue = $this->filterManager->getFilterValue('in_stock', $filters);

        return Product::query()
            ->select([
                'id',
                'title',
                'sku',
                'track_stock',
                'stock',
                'low_stock_threshold',
                'in_stock',
                'is_active',

            ])
            ->with([ // @phpstan-ignore argument.type
                'variants' => $this->getVariantFilter($inStockFilterValue),
            ])
            ->withFeaturedMedia()
            ->tap(fn (Builder $query): Builder => $this->filterManager->apply($query, $filters))
            ->paginate($perPage)
            ->through(function (Product $product) {
                $product->append(['featured_media', 'is_low_stock', 'total_stock']);
                $product->variants->each->append(['is_low_stock']);

                return $product->makeHidden(['mediaGallery']);
            })
            ->withQueryString();
    }

    /**
     * @return Closure(Relation<ProductVariant, Product, ProductVariant>): void
     */
    private function getVariantFilter(mixed $inStockFilterValue): Closure
    {
        $inputConverter = new InputConverter();
        $inStockFilter = $inputConverter->toNullableBoolean($inStockFilterValue);

        return function (Relation $query) use ($inStockFilter): void {
            $query->select([
                'id',
                'product_id',
                'title',
                'sku',
                'track_stock',
                'stock',
                'low_stock_threshold',
                'in_stock',
                'media_id',
            ])->with(['media:' . Media::displaySelect()]);

            if ($inStockFilter === true) {
                $query->where('in_stock', true);
            } elseif ($inStockFilter === false) {
                $query->where('in_stock', false);
            }
        };
    }
}
