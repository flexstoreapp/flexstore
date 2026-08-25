<?php

declare(strict_types=1);

use App\DTOs\OrderItemsSummary;
use App\Enums\WeightUnit;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Utilities\OrderUtility;
use Brick\Math\BigDecimal;
use Illuminate\Database\Eloquent\ModelNotFoundException;

covers(OrderItemsSummary::class);

uses()->group('dtos', 'checkout');

test('computes subtotal from product prices', function () {
    $product1 = Product::factory()->create(['price' => '25.0000']);
    $product2 = Product::factory()->create(['price' => '50.0000']);

    $items = [
        ['product_id' => $product1->id, 'product_variant_id' => null, 'quantity' => 2],
        ['product_id' => $product2->id, 'product_variant_id' => null, 'quantity' => 1],
    ];

    $data = OrderItemsSummary::fromItems($items);

    expect($data->subtotal)->toBe('100.0000');
});

test('uses variant price when variant is present', function () {
    $product = Product::factory()->create(['price' => '100.0000']);
    $variant = ProductVariant::factory()->for($product)->create(['price' => '75.0000']);

    $items = [
        ['product_id' => $product->id, 'product_variant_id' => $variant->id, 'quantity' => 2],
    ];

    $data = OrderItemsSummary::fromItems($items);

    expect($data->subtotal)->toBe('150.0000');
});

test('calculates total weight in grams', function () {
    $product = Product::factory()->create([
        'price' => '10.0000',
        'weight' => '1.5',
        'weight_unit' => WeightUnit::Kg,
    ]);

    $items = [
        ['product_id' => $product->id, 'product_variant_id' => null, 'quantity' => 2],
    ];

    $data = OrderItemsSummary::fromItems($items);

    expect($data->totalWeightInGrams->isEqualTo(BigDecimal::of('3000')))->toBeTrue();
});

test('uses variant weight when variant is present', function () {
    $product = Product::factory()->create([
        'price' => '10.0000',
        'weight' => '500',
        'weight_unit' => WeightUnit::G,
    ]);

    $variant = ProductVariant::factory()->for($product)->create([
        'price' => '12.0000',
        'weight' => '750',
        'weight_unit' => WeightUnit::G,
    ]);

    $items = [
        ['product_id' => $product->id, 'product_variant_id' => $variant->id, 'quantity' => 1],
    ];

    $data = OrderItemsSummary::fromItems($items);

    expect($data->totalWeightInGrams->isEqualTo(BigDecimal::of('750')))->toBeTrue();
});

test('collects product, category, and brand IDs', function () {
    $category1 = Category::factory()->create();
    $category2 = Category::factory()->create();
    $brand1 = Brand::factory()->create();
    $brand2 = Brand::factory()->create();
    $product1 = Product::factory()->for($category1)->for($brand1)->create(['price' => '10.0000']);
    $product2 = Product::factory()->for($category2)->for($brand2)->create(['price' => '20.0000']);

    $items = [
        ['product_id' => $product1->id, 'product_variant_id' => null, 'quantity' => 1],
        ['product_id' => $product2->id, 'product_variant_id' => null, 'quantity' => 1],
    ];

    $data = OrderItemsSummary::fromItems($items);

    expect($data->productIds)->toBe([$product1->id, $product2->id])
        ->and($data->categoryIds)->toContain($category1->id, $category2->id)
        ->and($data->brandIds)->toContain($brand1->id, $brand2->id);
});

test('handles empty items', function () {
    $data = OrderItemsSummary::fromItems([]);

    expect($data->subtotal)->toBe('0.0000')
        ->and($data->totalWeightInGrams->isZero())->toBeTrue()
        ->and($data->productIds)->toBe([])
        ->and($data->categoryIds)->toBe([])
        ->and($data->brandIds)->toBe([]);
});

test('throws exception for missing products', function () {
    $product = Product::factory()->create(['price' => '50.0000']);
    $deletedId = $product->id + 999;

    $items = [
        ['product_id' => $product->id, 'product_variant_id' => null, 'quantity' => 1],
        ['product_id' => $deletedId, 'product_variant_id' => null, 'quantity' => 1],
    ];

    OrderItemsSummary::fromItems($items);
})->throws(ModelNotFoundException::class);

test('handles products without weight', function () {
    $product = Product::factory()->create([
        'price' => '10.0000',
        'weight' => null,
        'weight_unit' => null,
    ]);

    $items = [
        ['product_id' => $product->id, 'product_variant_id' => null, 'quantity' => 3],
    ];

    $data = OrderItemsSummary::fromItems($items);

    expect($data->subtotal)->toBe('30.0000')
        ->and($data->totalWeightInGrams->isZero())->toBeTrue();
});

test('fromHydratedItems computes subtotal from hydrated items', function () {
    $product1 = Product::factory()->create(['price' => '25.0000']);
    $product2 = Product::factory()->create(['price' => '50.0000']);

    $hydratedItems = app(OrderUtility::class)->hydrateItems([
        ['product_id' => $product1->id, 'product_variant_id' => null, 'quantity' => 2, 'variant_options' => []],
        ['product_id' => $product2->id, 'product_variant_id' => null, 'quantity' => 1, 'variant_options' => []],
    ]);

    $data = OrderItemsSummary::fromHydratedItems($hydratedItems);

    expect($data->subtotal)->toBe('100.0000')
        ->and($data->productIds)->toBe([$product1->id, $product2->id]);
});

test('fromHydratedItems calculates weight from hydrated items', function () {
    $product = Product::factory()->create([
        'price' => '10.0000',
        'weight' => '1.5',
        'weight_unit' => WeightUnit::Kg,
    ]);

    $hydratedItems = app(OrderUtility::class)->hydrateItems([
        ['product_id' => $product->id, 'product_variant_id' => null, 'quantity' => 2, 'variant_options' => []],
    ]);

    $data = OrderItemsSummary::fromHydratedItems($hydratedItems);

    expect($data->totalWeightInGrams->isEqualTo(BigDecimal::of('3000')))->toBeTrue();
});

test('fromHydratedItems collects category and brand IDs', function () {
    $category1 = Category::factory()->create();
    $category2 = Category::factory()->create();
    $brand1 = Brand::factory()->create();
    $brand2 = Brand::factory()->create();
    $product1 = Product::factory()->for($category1)->for($brand1)->create(['price' => '10.0000']);
    $product2 = Product::factory()->for($category2)->for($brand2)->create(['price' => '20.0000']);

    $hydratedItems = app(OrderUtility::class)->hydrateItems([
        ['product_id' => $product1->id, 'product_variant_id' => null, 'quantity' => 1, 'variant_options' => []],
        ['product_id' => $product2->id, 'product_variant_id' => null, 'quantity' => 1, 'variant_options' => []],
    ]);

    $data = OrderItemsSummary::fromHydratedItems($hydratedItems);

    expect($data->categoryIds)->toContain($category1->id, $category2->id)
        ->and($data->brandIds)->toContain($brand1->id, $brand2->id);
});

test('fromHydratedItems handles empty array', function () {
    $data = OrderItemsSummary::fromHydratedItems([]);

    expect($data->subtotal)->toBe('0.0000')
        ->and($data->totalWeightInGrams->isZero())->toBeTrue()
        ->and($data->productIds)->toBe([])
        ->and($data->categoryIds)->toBe([])
        ->and($data->brandIds)->toBe([]);
});

test('fromItems produces same result as fromHydratedItems', function () {
    $product = Product::factory()->create(['price' => '75.0000', 'weight' => '2', 'weight_unit' => WeightUnit::Kg]);
    $variant = ProductVariant::factory()->for($product)->create(['price' => '65.0000', 'weight' => '1.8', 'weight_unit' => WeightUnit::Kg]);

    $items = [
        ['product_id' => $product->id, 'product_variant_id' => $variant->id, 'quantity' => 3],
    ];

    $fromItems = OrderItemsSummary::fromItems($items);

    $hydratedItems = app(OrderUtility::class)->hydrateItems([
        ['product_id' => $product->id, 'product_variant_id' => $variant->id, 'quantity' => 3, 'variant_options' => []],
    ]);
    $fromHydrated = OrderItemsSummary::fromHydratedItems($hydratedItems);

    expect($fromItems->subtotal)->toBe($fromHydrated->subtotal)
        ->and($fromItems->totalWeightInGrams->isEqualTo($fromHydrated->totalWeightInGrams))->toBeTrue()
        ->and($fromItems->productIds)->toBe($fromHydrated->productIds)
        ->and($fromItems->categoryIds)->toBe($fromHydrated->categoryIds)
        ->and($fromItems->brandIds)->toBe($fromHydrated->brandIds);
});
