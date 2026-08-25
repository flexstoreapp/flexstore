<?php

declare(strict_types=1);

use App\Models\Product;
use App\Models\ProductVariant;
use App\Queries\InventoryListQuery;
use Illuminate\Pagination\LengthAwarePaginator;

covers(InventoryListQuery::class);

uses()->group('queries', 'inventory');

test('returns paginated products', function () {
    Product::factory()->count(20)->create();

    $query = app(InventoryListQuery::class);
    $result = $query->execute(perPage: 15);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result)->toHaveCount(15)
        ->and($result->total())->toBe(20);
});

test('returns empty paginator when no products exist', function () {
    $query = app(InventoryListQuery::class);
    $result = $query->execute();

    expect($result)->toBeEmpty();
});

test('appends featured media and stock attributes', function () {
    Product::factory()->withMedia(1)->create([
        'track_stock' => true,
        'stock' => 10,
        'low_stock_threshold' => 5,
    ]);

    $query = app(InventoryListQuery::class);
    $result = $query->execute();

    $product = $result->first();

    expect($product->featured_media)->not->toBeNull()
        ->and($product->is_low_stock)->not->toBeNull()
        ->and($product->total_stock)->not->toBeNull();
});

test('hides media from results', function () {
    Product::factory()->create();

    $query = app(InventoryListQuery::class);
    $result = $query->execute();

    $productArray = $result->first()->toArray();

    expect($productArray)->not->toHaveKey('media_gallery');
});

test('loads variants with stock attributes', function () {
    $product = Product::factory()->create();
    ProductVariant::factory()->create([
        'product_id' => $product->id,
        'stock' => 5,
    ]);

    $query = app(InventoryListQuery::class);
    $result = $query->execute();

    $product = $result->first();

    expect($product->relationLoaded('variants'))->toBeTrue()
        ->and($product->variants)->toHaveCount(1);
});

test('appends variant featured media and low stock attributes', function () {
    $product = Product::factory()->create();
    ProductVariant::factory()->create([
        'product_id' => $product->id,
        'stock' => 5,
    ]);

    $query = app(InventoryListQuery::class);
    $result = $query->execute();

    $variant = $result->first()->variants->first();

    expect($variant->is_low_stock)->not->toBeNull()
        ->and($variant->media)->toBeNull();
});

test('respects per page parameter', function () {
    Product::factory()->count(10)->create();

    $query = app(InventoryListQuery::class);
    $result = $query->execute(perPage: 5);

    expect($result)->toHaveCount(5)
        ->and($result->total())->toBe(10);
});
