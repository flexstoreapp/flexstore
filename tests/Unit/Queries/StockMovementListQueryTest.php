<?php

declare(strict_types=1);

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Queries\StockMovementListQuery;
use Illuminate\Pagination\LengthAwarePaginator;

covers(StockMovementListQuery::class);

uses()->group('queries', 'inventory');

test('returns paginated stock movements for a product', function () {
    $product = Product::factory()->create();
    StockMovement::factory()->count(3)->forProduct($product)->create();

    $query = new StockMovementListQuery();
    $result = $query->execute($product);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result)->toHaveCount(3);
});

test('returns paginated stock movements for a variant', function () {
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
    StockMovement::factory()->count(2)->forVariant($variant)->create();

    $query = new StockMovementListQuery();
    $result = $query->execute($variant);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result)->toHaveCount(2);
});

test('eager loads user relation', function () {
    $product = Product::factory()->create();
    StockMovement::factory()->forProduct($product)->create();

    $query = new StockMovementListQuery();
    $result = $query->execute($product);

    expect($result->first()->relationLoaded('user'))->toBeTrue();
});

test('respects per page parameter', function () {
    $product = Product::factory()->create();
    StockMovement::factory()->count(10)->forProduct($product)->create();

    $query = new StockMovementListQuery();
    $result = $query->execute($product, 5);

    expect($result)->toHaveCount(5)
        ->and($result->total())->toBe(10);
});

test('orders by latest', function () {
    $product = Product::factory()->create();
    $older = StockMovement::factory()->forProduct($product)->create(['created_at' => now()->subDay()]);
    $newer = StockMovement::factory()->forProduct($product)->create(['created_at' => now()]);

    $query = new StockMovementListQuery();
    $result = $query->execute($product);

    expect($result->first()->id)->toBe($newer->id);
});

test('returns empty paginator when no movements exist', function () {
    $product = Product::factory()->create();

    $query = new StockMovementListQuery();
    $result = $query->execute($product);

    expect($result)->toBeEmpty();
});
