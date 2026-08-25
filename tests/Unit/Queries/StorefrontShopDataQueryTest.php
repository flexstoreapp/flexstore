<?php

declare(strict_types=1);

namespace Tests\Unit\Queries;

use App\DTOs\ProductFilterInput;
use App\Enums\ListLoadingMethod;
use App\Enums\ProductSortOption;
use App\Models\Product;
use App\Models\Setting;
use App\Queries\StorefrontShopDataQuery;
use Closure;

covers(StorefrontShopDataQuery::class);

uses()->group('queries');

/**
 * @return array<string, mixed>
 */
function shopData(ProductFilterInput $input = new ProductFilterInput()): array
{
    return array_map(fn (Closure $prop) => $prop(), app(StorefrontShopDataQuery::class)->execute($input));
}

test('returns products, facets, filters and loading method', function () {
    Product::factory()->count(3)->active()->create();

    $data = shopData();

    expect($data)->toHaveKeys(['products', 'facets', 'filters', 'loadingMethod'])
        ->and($data['products']->total())->toBe(3)
        ->and($data['facets'])->toHaveKeys(['categories', 'brands', 'price_buckets', 'rating_buckets']);
});

test('echoes the applied filters from the input', function () {
    $data = shopData(new ProductFilterInput(
        search: 'shoes',
        categoryIds: [1, 2],
        brandIds: [3],
        minPrice: '10',
        maxPrice: '90',
        inStock: true,
        onSale: true,
        rating: 4.0,
        sort: ProductSortOption::PriceLowHigh->value,
        perPage: 36,
    ));

    expect($data['filters'])
        ->toMatchArray([
            'query' => 'shoes',
            'categories' => [1, 2],
            'brands' => [3],
            'min_price' => '10',
            'max_price' => '90',
            'in_stock' => true,
            'on_sale' => true,
            'rating' => 4.0,
            'sort' => ProductSortOption::PriceLowHigh->value,
            'per_page' => 36,
        ]);
});

test('falls back to the configured default sort when none is requested', function () {
    Setting::setValue('storefront_product_list_default_sort', ProductSortOption::NameAZ->value);

    expect(shopData()['filters']['sort'])->toBe(ProductSortOption::NameAZ->value);
});

test('uses relevance sort when a search term is present and no sort is requested', function () {
    expect(shopData(new ProductFilterInput(search: 'shoes'))['filters']['sort'])
        ->toBe(ProductSortOption::Relevance->value);
});

test('exposes the configured loading method', function () {
    Setting::setValue('storefront_product_list_loading_method', ListLoadingMethod::InfiniteScroll->value);

    expect(shopData()['loadingMethod'])->toBe(ListLoadingMethod::InfiniteScroll->value);
});

test('context flags exclude the matching facet', function () {
    $categoryFacets = (app(StorefrontShopDataQuery::class)->execute(new ProductFilterInput(contextCategoryId: 5))['facets'])();
    $brandFacets = (app(StorefrontShopDataQuery::class)->execute(new ProductFilterInput(contextBrandId: 5))['facets'])();

    expect($categoryFacets['categories'])->toBeNull()
        ->and($brandFacets['brands'])->toBeNull();
});
