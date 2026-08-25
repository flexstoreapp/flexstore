<?php

declare(strict_types=1);

use App\DTOs\ProductFilterInput;
use App\Enums\ListLoadingMethod;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Queries\StorefrontProductListQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

covers(StorefrontProductListQuery::class);

uses()->group('product-list');

test('returns paginated products', function () {
    Product::factory()->count(5)->active()->create();

    $query = app(StorefrontProductListQuery::class);
    $result = $query->execute();

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result)->toHaveCount(5);
});

test('only returns active products', function () {
    Product::factory()->count(3)->active()->create();
    Product::factory()->count(2)->inactive()->create();

    $query = app(StorefrontProductListQuery::class);
    $result = $query->execute();

    expect($result)->toHaveCount(3);
});

test('filters by category id including descendants', function () {
    $parent = Category::factory()->create();
    $child = Category::factory()->create(['parent_id' => $parent->id]);

    Product::factory()->count(2)->active()->create(['category_id' => $parent->id]);
    Product::factory()->count(3)->active()->create(['category_id' => $child->id]);
    Product::factory()->count(4)->active()->create();

    $query = app(StorefrontProductListQuery::class);
    $result = $query->execute(new ProductFilterInput(contextCategoryId: $parent->id));

    expect($result)->toHaveCount(5);
});

test('filters by brand id', function () {
    $brand = Brand::factory()->create();

    Product::factory()->count(3)->active()->create(['brand_id' => $brand->id]);
    Product::factory()->count(2)->active()->create();

    $query = app(StorefrontProductListQuery::class);
    $result = $query->execute(new ProductFilterInput(contextBrandId: $brand->id));

    expect($result)->toHaveCount(3);
});

test('returns simple paginator for infinite scroll', function () {
    Setting::setValue('storefront_product_list_loading_method', ListLoadingMethod::InfiniteScroll->value);
    Product::factory()->count(5)->active()->create();

    $query = app(StorefrontProductListQuery::class);
    $result = $query->execute();

    expect($result)->toBeInstanceOf(Paginator::class);
});

test('simple paginator can be serialized for response', function () {
    Setting::setValue('storefront_product_list_loading_method', ListLoadingMethod::InfiniteScroll->value);
    Setting::setValue('storefront_product_list_default_per_page', 2);
    Product::factory()->count(5)->active()->create();

    $query = app(StorefrontProductListQuery::class);
    $result = $query->execute();

    $array = $result->toArray();

    expect($array)->toHaveKeys(['data', 'first_page_url', 'next_page_url', 'path', 'per_page', 'prev_page_url'])
        ->and($array['data'])->toHaveCount(2)
        ->and($array['next_page_url'])->not->toBeNull();
});

test('respects products per page setting', function () {
    Setting::setValue('storefront_product_list_default_per_page', 12);
    Product::factory()->count(20)->active()->create();

    $query = app(StorefrontProductListQuery::class);
    $result = $query->execute();

    expect($result)->toHaveCount(12);
});

test('transforms product data correctly', function () {
    $product = Product::factory()->create([
        'is_active' => true,
        'title' => ['en' => 'Test Product'],
        'price' => 99.99,
    ]);

    $query = app(StorefrontProductListQuery::class);
    $result = $query->execute();

    $item = $result->first();

    expect($item)->toHaveKeys([
        'id',
        'url_handle',
        'title',
        'price',
        'price_range',
        'compare_at_price',
        'featured_media',
        'in_stock',
        'rating',
        'review_count',
        'has_variants',
        'created_at',
    ])
        ->and($item['id'])->toBe($product->id)
        ->and($item['title'])->toBe(['en' => 'Test Product']);
});

test('determines in_stock from variants when product has variants', function () {
    $product = Product::factory()->create(['is_active' => true, 'in_stock' => false]);
    ProductVariant::factory()->create([
        'product_id' => $product->id,
        'in_stock' => true,
    ]);

    $query = app(StorefrontProductListQuery::class);
    $result = $query->execute();

    expect($result->first()['in_stock'])->toBeTrue()
        ->and($result->first()['has_variants'])->toBeTrue();
});

test('uses product in_stock when no variants exist', function () {
    Product::factory()->create(['is_active' => true, 'in_stock' => true]);
    Product::factory()->create(['is_active' => true, 'in_stock' => false]);

    $query = app(StorefrontProductListQuery::class);
    $result = $query->execute();

    $items = $result->items();
    $inStockItem = collect($items)->firstWhere('in_stock', true);
    $outOfStockItem = collect($items)->firstWhere('in_stock', false);

    expect($inStockItem)->not->toBeNull()
        ->and($outOfStockItem)->not->toBeNull();
});
