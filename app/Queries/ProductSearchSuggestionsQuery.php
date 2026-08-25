<?php

declare(strict_types=1);

namespace App\Queries;

use App\Enums\ProductSortOption;
use App\Filters\Configs\ProductFilterConfig;
use App\Filters\FilterManager;
use App\Models\Media;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final readonly class ProductSearchSuggestionsQuery
{
    private const int MAX_RESULTS = 5;

    private const int MIN_QUERY_LENGTH = 2;

    /**
     * @param  FilterManager<Product>  $filterManager
     */
    public function __construct(private FilterManager $filterManager)
    {
    }

    /**
     * @return array{
     *     suggestions: Collection<int, array{
     *         id: int,
     *         url_handle: string,
     *         title: array<string, string>,
     *         category: array<string, string>|null,
     *         price: string|null,
     *         compare_at_price: string|null,
     *         price_range: array{0: string, 1: string}|null,
     *         compare_at_price_range: array{0: string, 1: string}|null,
     *         featured_media: Media|null,
     *         in_stock: bool|null,
     *     }>,
     *     total: int,
     *     has_more: bool,
     *     query: string,
     * }
     */
    public function execute(string $query): array
    {
        $query = mb_trim($query);

        if ($query === '' || mb_strlen($query) < self::MIN_QUERY_LENGTH) {
            return [
                'suggestions' => new Collection(),
                'total' => 0,
                'has_more' => false,
                'query' => $query,
            ];
        }

        $this->filterManager->setCriteria(ProductFilterConfig::getStorefrontCriteria($query));

        $baseQuery = Product::query()
            ->with([
                'category:id,name',
                'variants:id,product_id,price,compare_at_price,in_stock',
            ])
            ->withFeaturedMedia()
            ->select(['id', 'url_handle', 'title', 'category_id', 'price', 'compare_at_price', 'in_stock'])
            ->where('is_active', true)
            ->tap(fn (Builder $builder): Builder => $this->filterManager->apply($builder, [
                'query' => $query,
                'sort' => ProductSortOption::Relevance->value,
            ]));

        $total = $baseQuery->toBase()->getCountForPagination();

        $products = $baseQuery
            ->limit(self::MAX_RESULTS)
            ->get();

        $suggestions = $products->map(fn (Product $product): array => [
            'id' => $product->id,
            'url_handle' => $product->url_handle,
            'title' => $product->getTranslations('title'),
            'category' => $product->category?->getTranslations('name'),
            'price' => $product->price,
            'compare_at_price' => $product->compare_at_price,
            'compare_at_price_range' => $product->compare_at_price_range,
            'price_range' => $product->price_range,
            'featured_media' => $product->featured_media,
            'in_stock' => $product->variants->isNotEmpty()
                ? $product->variants->contains('in_stock', true)
                : $product->in_stock,
        ]);

        return [ // @phpstan-ignore return.type
            'suggestions' => $suggestions,
            'total' => $total,
            'has_more' => $total > self::MAX_RESULTS,
            'query' => $query,
        ];
    }
}
