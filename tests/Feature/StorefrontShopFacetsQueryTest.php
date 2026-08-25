<?php

declare(strict_types=1);

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Review;
use App\Queries\StorefrontShopFacetsQuery;
use Illuminate\Support\Facades\DB;

covers(StorefrontShopFacetsQuery::class);

uses()->group('product-list');

test('returns root categories that have products with counts', function () {
    $parent = Category::factory()->active()->create();
    $child = Category::factory()->active()->create(['parent_id' => $parent->id]);
    Category::factory()->active()->create();

    Product::factory()->count(2)->active()->create(['category_id' => $parent->id]);
    Product::factory()->active()->create(['category_id' => $child->id]);

    $facets = app(StorefrontShopFacetsQuery::class)->execute();

    expect($facets['categories'])->toHaveCount(1)
        ->and($facets['categories'][0]['id'])->toBe($parent->id)
        ->and($facets['categories'][0]['count'])->toBe(3);
});

test('scopes brand facets to the category context', function () {
    $electronics = Category::factory()->active()->create();
    $fashion = Category::factory()->active()->create();
    $inScope = Brand::factory()->active()->create();
    $outOfScope = Brand::factory()->active()->create();

    Product::factory()->active()->create(['category_id' => $electronics->id, 'brand_id' => $inScope->id]);
    Product::factory()->active()->create(['category_id' => $fashion->id, 'brand_id' => $outOfScope->id]);

    $facets = app(StorefrontShopFacetsQuery::class)->execute(
        contextCategoryId: $electronics->id,
        includeCategories: false,
    );

    expect($facets['brands'])->toHaveCount(1)
        ->and($facets['brands'][0]['id'])->toBe($inScope->id)
        ->and($facets['brands'][0]['count'])->toBe(1);
});

test('scopes facets to the search query', function () {
    $matching = Brand::factory()->active()->create();
    $other = Brand::factory()->active()->create();

    Product::factory()->active()->create(['title' => ['en' => 'Blue Widget'], 'brand_id' => $matching->id]);
    Product::factory()->active()->create(['title' => ['en' => 'Red Gadget'], 'brand_id' => $other->id]);

    $facets = app(StorefrontShopFacetsQuery::class)->execute(search: 'Widget');

    expect($facets['brands'])->toHaveCount(1)
        ->and($facets['brands'][0]['id'])->toBe($matching->id);
});

test('returns active brands with product counts', function () {
    $brand = Brand::factory()->active()->create();
    Brand::factory()->active()->create();
    Product::factory()->count(2)->active()->create(['brand_id' => $brand->id]);

    $facets = app(StorefrontShopFacetsQuery::class)->execute();

    expect($facets['brands'])->toHaveCount(1)
        ->and($facets['brands'][0]['id'])->toBe($brand->id)
        ->and($facets['brands'][0]['count'])->toBe(2);
});

test('builds price buckets from the product price range', function () {
    Product::factory()->active()->create(['price' => 10]);
    Product::factory()->active()->create(['price' => 400]);

    $facets = app(StorefrontShopFacetsQuery::class)->execute();

    expect($facets['price_buckets'])->toHaveCount(4)
        ->and($facets['price_buckets'][0]['min'])->toBeNull()
        ->and($facets['price_buckets'][3]['max'])->toBeNull()
        ->and($facets['price_buckets'][0]['max'])->toBe($facets['price_buckets'][1]['min']);
});

test('returns no price buckets when prices do not vary', function () {
    Product::factory()->count(3)->active()->create(['price' => 50]);

    expect(app(StorefrontShopFacetsQuery::class)->execute()['price_buckets'])->toBe([]);
});

test('only offers rating buckets that have matching products', function () {
    $product = Product::factory()->active()->create();
    Review::factory()->approved()->create(['product_id' => $product->id, 'rating' => 4]);

    $buckets = app(StorefrontShopFacetsQuery::class)->execute()['rating_buckets'];

    expect($buckets)->toContain(4.0)
        ->and($buckets)->toContain(3.0)
        ->and($buckets)->not->toContain(4.5);
});

test('rating buckets match products through a subquery instead of binding every product id', function () {
    $product = Product::factory()->active()->create();
    Review::factory()->approved()->create(['product_id' => $product->id, 'rating' => 4]);
    Product::factory()->count(20)->active()->create();

    DB::flushQueryLog();
    DB::enableQueryLog();
    app(StorefrontShopFacetsQuery::class)->execute();
    $log = DB::getQueryLog();
    DB::disableQueryLog();

    $ratingQuery = collect($log)->first(fn (array $entry): bool => str_contains($entry['query'], 'avg(rating)'));

    expect($ratingQuery)->not->toBeNull()
        ->and(loggedSql($ratingQuery['query']))->toContain('select id from products')
        ->and($ratingQuery['bindings'])->toHaveCount(2);
});

test('omits facets when context excludes them', function () {
    Category::factory()->active()->has(Product::factory()->active())->create();
    Brand::factory()->active()->has(Product::factory()->active())->create();

    $facets = app(StorefrontShopFacetsQuery::class)->execute(includeCategories: false, includeBrands: false);

    expect($facets['categories'])->toBeNull()
        ->and($facets['brands'])->toBeNull();
});
