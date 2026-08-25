<?php

declare(strict_types=1);

namespace App\Queries;

use App\Filters\Configs\ProductFilterConfig;
use App\Filters\FilterManager;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

final readonly class ProductListQuery
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
        $this->filterManager->setCriteria(ProductFilterConfig::getCriteria($filters['direction'] ?? null));

        return Product::query()
            ->select([
                'id',
                'title',
                'price',
                'category_id',
                'stock',
                'track_stock',
                'is_active',
            ])
            ->with([
                'category:id,name',
                'variants:product_id,stock,price,track_stock',
            ])
            ->withFeaturedMedia()
            ->tap(fn (Builder $query): Builder => $this->filterManager->apply($query, $filters))
            ->paginate($perPage)
            ->through(fn (Product $product) => $product->append(['featured_media', 'total_stock', 'price_range']))
            ->through(fn (Product $product) => $product->makeHidden(['mediaGallery', 'stock']))
            ->withQueryString();
    }
}
