<?php

declare(strict_types=1);

namespace Tests\Unit\Filters\Criteria;

use App\Filters\Criteria\InStockCriterion;
use App\Models\Product;

covers(InStockCriterion::class);

uses()->group('filters');

test('canApply returns true for non-empty values', function () {
    $criterion = new InStockCriterion();

    expect($criterion->canApply('true'))->toBeTrue()
        ->and($criterion->canApply('false'))->toBeTrue()
        ->and($criterion->canApply('1'))->toBeTrue()
        ->and($criterion->canApply(true))->toBeTrue()
        ->and($criterion->canApply(false))->toBeTrue();
});

test('canApply returns false for empty values', function () {
    $criterion = new InStockCriterion();

    expect($criterion->canApply(null))->toBeFalse()
        ->and($criterion->canApply(''))->toBeFalse();
});

test('filters products that are in stock', function () {
    Product::factory()->create([
        'track_stock' => true,
        'stock' => 10,
        'in_stock' => true,
    ]);

    Product::factory()->create([
        'track_stock' => true,
        'stock' => 0,
        'in_stock' => false,
    ]);

    $criterion = new InStockCriterion();
    $builder = Product::query();

    $result = $criterion->apply($builder, 'true');

    expect($result->count())->toBe(1)
        ->and($result->first()->in_stock)->toBeTrue();
});

test('filters products that are out of stock', function () {
    Product::factory()->create([
        'track_stock' => true,
        'stock' => 10,
        'in_stock' => true,
    ]);

    Product::factory()->create([
        'track_stock' => true,
        'stock' => 0,
        'in_stock' => false,
    ]);

    $criterion = new InStockCriterion();
    $builder = Product::query();

    $result = $criterion->apply($builder, 'false');

    expect($result->count())->toBe(1)
        ->and($result->first()->in_stock)->toBeFalse();
});

test('includes products with in stock variants when filtering for in stock', function () {
    $product1 = Product::factory()->create([
        'track_stock' => true,
        'stock' => 10,
        'in_stock' => true,
    ]);

    $product2 = Product::factory()->create([
        'track_stock' => true,
        'stock' => 0,
        'in_stock' => false,
    ]);

    $product2->variants()->create([
        'title' => 'Variant 1',
        'price' => '10.0000',
        'track_stock' => true,
        'stock' => 5,
        'in_stock' => true,
    ]);

    $product3 = Product::factory()->create([
        'track_stock' => true,
        'stock' => 0,
        'in_stock' => false,
    ]);

    $criterion = new InStockCriterion();
    $builder = Product::query();

    $result = $criterion->apply($builder, 'true');

    expect($result->count())->toBe(2)
        ->and($result->pluck('id')->toArray())->toContain($product1->id)
        ->and($result->pluck('id')->toArray())->toContain($product2->id)
        ->and($result->pluck('id')->toArray())->not->toContain($product3->id);
});

test('includes products with out of stock variants when filtering for out of stock', function () {
    $product1 = Product::factory()->create([
        'track_stock' => true,
        'stock' => 0,
        'in_stock' => false,
    ]);

    $product2 = Product::factory()->create([
        'track_stock' => true,
        'stock' => 10,
        'in_stock' => true,
    ]);

    $product2->variants()->create([
        'title' => 'Variant 1',
        'price' => '10.0000',
        'track_stock' => true,
        'stock' => 0,
        'in_stock' => false,
    ]);

    $product3 = Product::factory()->create([
        'track_stock' => true,
        'stock' => 10,
        'in_stock' => true,
    ]);

    $criterion = new InStockCriterion();
    $builder = Product::query();

    $result = $criterion->apply($builder, 'false');

    expect($result->count())->toBe(2)
        ->and($result->pluck('id')->toArray())->toContain($product1->id)
        ->and($result->pluck('id')->toArray())->toContain($product2->id)
        ->and($result->pluck('id')->toArray())->not->toContain($product3->id);
});

test('handles boolean true value', function () {
    Product::factory()->create([
        'track_stock' => true,
        'stock' => 10,
        'in_stock' => true,
    ]);

    Product::factory()->create([
        'track_stock' => true,
        'stock' => 0,
        'in_stock' => false,
    ]);

    $criterion = new InStockCriterion();
    $builder = Product::query();

    $result = $criterion->apply($builder, true);

    expect($result->count())->toBe(1);
});

test('handles boolean false value', function () {
    Product::factory()->create([
        'track_stock' => true,
        'stock' => 10,
        'in_stock' => true,
    ]);

    Product::factory()->create([
        'track_stock' => true,
        'stock' => 0,
        'in_stock' => false,
    ]);

    $criterion = new InStockCriterion();
    $builder = Product::query();

    $result = $criterion->apply($builder, false);

    expect($result->count())->toBe(1);
});

test('handles string boolean values', function () {
    Product::factory()->create([
        'track_stock' => true,
        'stock' => 10,
        'in_stock' => true,
    ]);

    $criterion = new InStockCriterion();
    $builder = Product::query();

    expect($criterion->apply($builder, '1')->count())->toBe(1)
        ->and($criterion->apply($builder, 'yes')->count())->toBe(1)
        ->and($criterion->apply($builder, 'on')->count())->toBe(1);
});

test('product with variants ignores product in_stock and uses variant in_stock when filtering for in stock', function () {
    $productWithVariantsInStock = Product::factory()->create([
        'in_stock' => false,
    ]);

    $productWithVariantsInStock->variants()->create([
        'title' => 'Variant 1',
        'price' => '10.0000',
        'track_stock' => true,
        'stock' => 5,
        'in_stock' => true,
    ]);

    $productWithVariantsOutOfStock = Product::factory()->create([
        'in_stock' => true,
    ]);

    $productWithVariantsOutOfStock->variants()->create([
        'title' => 'Variant 1',
        'price' => '10.0000',
        'track_stock' => true,
        'stock' => 0,
        'in_stock' => false,
    ]);

    $criterion = new InStockCriterion();

    $result = $criterion->apply(Product::query(), 'true');

    expect($result->count())->toBe(1)
        ->and($result->first()->id)->toBe($productWithVariantsInStock->id);
});

test('product with variants ignores product in_stock and uses variant in_stock when filtering for out of stock', function () {
    $productWithVariantsInStock = Product::factory()->create([
        'in_stock' => false,
    ]);

    $productWithVariantsInStock->variants()->create([
        'title' => 'Variant 1',
        'price' => '10.0000',
        'track_stock' => true,
        'stock' => 5,
        'in_stock' => true,
    ]);

    $productWithVariantsOutOfStock = Product::factory()->create([
        'in_stock' => true,
    ]);

    $productWithVariantsOutOfStock->variants()->create([
        'title' => 'Variant 1',
        'price' => '10.0000',
        'track_stock' => true,
        'stock' => 0,
        'in_stock' => false,
    ]);

    $criterion = new InStockCriterion();

    $result = $criterion->apply(Product::query(), 'false');

    expect($result->count())->toBe(1)
        ->and($result->first()->id)->toBe($productWithVariantsOutOfStock->id);
});

test('product with mixed variant stock status appears in both filters', function () {
    $product = Product::factory()->create([
        'in_stock' => false,
    ]);

    $product->variants()->create([
        'title' => 'Variant 1',
        'price' => '10.0000',
        'track_stock' => true,
        'stock' => 0,
        'in_stock' => false,
    ]);

    $product->variants()->create([
        'title' => 'Variant 2',
        'price' => '15.0000',
        'track_stock' => true,
        'stock' => 5,
        'in_stock' => true,
    ]);

    $criterion = new InStockCriterion();

    $inStockResult = $criterion->apply(Product::query(), 'true');
    $outOfStockResult = $criterion->apply(Product::query(), 'false');

    // Product appears in both filters because it has variants matching each status
    expect($inStockResult->count())->toBe(1)
        ->and($inStockResult->first()->id)->toBe($product->id)
        ->and($outOfStockResult->count())->toBe(1)
        ->and($outOfStockResult->first()->id)->toBe($product->id);
});
