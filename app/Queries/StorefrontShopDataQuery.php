<?php

declare(strict_types=1);

namespace App\Queries;

use App\DTOs\ProductFilterInput;
use App\Enums\ListLoadingMethod;
use App\Enums\ProductSortOption;
use App\Models\Setting;
use Closure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator;

final readonly class StorefrontShopDataQuery
{
    public function __construct(
        private StorefrontProductListQuery $productListQuery,
        private StorefrontShopFacetsQuery $facetsQuery,
    ) {
    }

    /**
     * @return array{products: Closure(): (LengthAwarePaginator<int, array<string, mixed>>|Paginator<int, array<string, mixed>>), facets: Closure(): array{categories: list<array<string, mixed>>|null, brands: list<array<string, mixed>>|null, price_buckets: list<array{min: string|null, max: string|null}>, rating_buckets: list<float>}, filters: Closure(): array<string, mixed>, loadingMethod: Closure(): string}
     */
    public function execute(ProductFilterInput $input = new ProductFilterInput()): array
    {
        $products = null;
        $resolveProducts = function () use (&$products, $input): Paginator {
            return $products ??= $this->productListQuery->execute($input);
        };

        return [
            'products' => $resolveProducts,
            'facets' => fn (): array => $this->facetsQuery->execute(
                contextCategoryId: $input->contextCategoryId,
                contextBrandId: $input->contextBrandId,
                search: $input->search,
                includeCategories: $input->contextCategoryId === null,
                includeBrands: $input->contextBrandId === null,
            ),
            'filters' => fn (): array => $this->filters($input, $resolveProducts()->perPage()),
            'loadingMethod' => fn (): string => (string) Setting::getValue('storefront_product_list_loading_method', ListLoadingMethod::Pagination->value),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function filters(ProductFilterInput $input, int $perPage): array
    {
        $default = (string) Setting::getValue('storefront_product_list_default_sort', ProductSortOption::Latest->value);

        return [
            'query' => $input->search,
            'categories' => $input->categoryIds,
            'brands' => $input->brandIds,
            'min_price' => $input->minPrice,
            'max_price' => $input->maxPrice,
            'in_stock' => $input->inStock,
            'on_sale' => $input->onSale,
            'rating' => $input->rating,
            'sort' => ProductSortOption::resolve($input->sort, $input->hasSearch(), $default),
            'per_page' => $perPage,
        ];
    }
}
