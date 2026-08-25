<?php

declare(strict_types=1);

use App\Actions\SyncMediaAction;
use App\Models\Media;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Queries\ProductListQuery;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

covers(ProductListQuery::class);

uses()->group('queries', 'product');

test('returns paginated products', function () {
    Product::factory()->count(20)->create();

    $query = app(ProductListQuery::class);
    $result = $query->execute(perPage: 15);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result)->toHaveCount(15)
        ->and($result->total())->toBe(20);
});

test('returns empty paginator when no products exist', function () {
    $query = app(ProductListQuery::class);
    $result = $query->execute();

    expect($result)->toBeEmpty();
});

test('respects per page parameter', function () {
    Product::factory()->count(10)->create();

    $query = app(ProductListQuery::class);
    $result = $query->execute(perPage: 5);

    expect($result)->toHaveCount(5)
        ->and($result->total())->toBe(10)
        ->and($result->lastPage())->toBe(2);
});

test('defaults to 15 per page', function () {
    Product::factory()->count(20)->create();

    $query = app(ProductListQuery::class);
    $result = $query->execute();

    expect($result)->toHaveCount(15)
        ->and($result->perPage())->toBe(15);
});

test('includes featured media and price range appends', function () {
    $product = Product::factory()->create();
    ProductVariant::factory()->create(['product_id' => $product->id, 'is_default' => true]);
    $media = Media::factory()->create();
    (new SyncMediaAction())->handle($product, [$media->id]);

    $query = app(ProductListQuery::class);
    $result = $query->execute();

    $product = $result->first();

    expect($product->featured_media)->not->toBeNull()
        ->and($product->featured_media->id)->toBe($media->id)
        ->and($product->total_stock)->not->toBeNull()
        ->and($product->price_range)->not->toBeNull();
});

test('exposes track stock flag', function () {
    Product::factory()->create(['track_stock' => false]);

    $query = app(ProductListQuery::class);
    $result = $query->execute();

    expect($result->first()->track_stock)->toBeFalse();
});

test('exposes variant track stock flag', function () {
    $product = Product::factory()->create(['track_stock' => null]);
    ProductVariant::factory()->create(['product_id' => $product->id, 'track_stock' => false, 'stock' => null]);
    ProductVariant::factory()->create(['product_id' => $product->id, 'track_stock' => true, 'stock' => 4]);

    $query = app(ProductListQuery::class);
    $variants = $query->execute()->first()->variants;

    expect($variants->pluck('track_stock')->all())->toEqualCanonicalizing([false, true]);
});

test('hides media and stock from results', function () {
    Product::factory()->create();

    $query = app(ProductListQuery::class);
    $result = $query->execute();

    $productArray = $result->first()->toArray();

    expect($productArray)->not->toHaveKey('media_gallery')
        ->and($productArray)->not->toHaveKey('stock');
});

test('loads category relationship', function () {
    $category = App\Models\Category::factory()->create();
    Product::factory()->create(['category_id' => $category->id]);

    $query = app(ProductListQuery::class);
    $result = $query->execute();

    expect($result->first()->relationLoaded('category'))->toBeTrue()
        ->and($result->first()->category->id)->toBe($category->id);
});

test('loads variant relationships', function () {
    $product = Product::factory()->create();
    ProductVariant::factory()->count(2)->create(['product_id' => $product->id]);

    $query = app(ProductListQuery::class);
    $result = $query->execute();

    expect($result->first()->relationLoaded('variants'))->toBeTrue()
        ->and($result->first()->variants)->toHaveCount(2);
});

test('featured media query count does not scale with product count or gallery size', function () {
    $seedProducts = function (int $count, int $galleryImages): void {
        Product::factory()
            ->count($count)
            ->create()
            ->each(function (Product $product) use ($galleryImages): void {
                $media = Media::factory()->count($galleryImages)->create();
                (new SyncMediaAction())->handle($product, $media->pluck('id')->all());
            });
    };

    $countQueries = function (): int {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $result = app(ProductListQuery::class)->execute();
        $result->each(fn (Product $product) => $product->featured_media);

        $queryCount = count(DB::getQueryLog());

        DB::disableQueryLog();

        return $queryCount;
    };

    $seedProducts(2, 1);
    $smallQueryCount = $countQueries();

    Product::query()->delete();

    $seedProducts(8, 6);
    $largeQueryCount = $countQueries();

    expect($largeQueryCount)->toBe($smallQueryCount);
});
