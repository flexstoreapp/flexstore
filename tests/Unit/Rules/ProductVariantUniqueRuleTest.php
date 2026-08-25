<?php

declare(strict_types=1);

use App\Models\Product;
use App\Models\ProductVariant;
use App\Rules\ProductVariantUniqueRule;

covers(ProductVariantUniqueRule::class);

uses()->group('rules', 'product');

test('validation passes when value is unique across products and variants', function () {
    $product = Product::factory()->create([
        'sku' => 'PRODUCT-SKU-1',
    ]);

    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'sku' => 'VARIANT-SKU-1',
    ]);

    $rule = new ProductVariantUniqueRule('sku', 'SKU');
    $rule->setData([
        'variants' => [
            [
                'id' => $variant->id,
                'name' => 'Variant 1',
                'sku' => 'UNIQUE-SKU',
            ],
        ],
    ]);

    $failClosureCalled = false;

    $rule->validate('variants.0.sku', 'UNIQUE-SKU', function () use (&$failClosureCalled) {
        $failClosureCalled = true;
    });

    expect($failClosureCalled)->toBeFalse();
});

test('validation fails when value exists in products table', function () {
    Product::factory()->create([
        'sku' => 'DUPLICATE-SKU',
    ]);

    $rule = new ProductVariantUniqueRule('sku', 'SKU');
    $rule->setData([
        'variants' => [
            [
                'id' => 'new-variant',
                'name' => 'New Variant',
                'sku' => 'DUPLICATE-SKU', // Same as existing product SKU
            ],
        ],
    ]);

    $failClosureCalled = false;

    $rule->validate('variants.0.sku', 'DUPLICATE-SKU', function () use (&$failClosureCalled) {
        $failClosureCalled = true;
    });

    expect($failClosureCalled)->toBeTrue();
});

test('validation fails when value exists in product_variants table', function () {
    $product = Product::factory()->create([
        'sku' => 'PRODUCT-SKU-2',
    ]);

    ProductVariant::factory()->create([
        'product_id' => $product->id,
        'sku' => 'DUPLICATE-VARIANT-SKU',
    ]);

    $rule = new ProductVariantUniqueRule('sku', 'SKU');
    $rule->setData([
        'variants' => [
            [
                'id' => 'new-variant',
                'name' => 'New Variant',
                'sku' => 'DUPLICATE-VARIANT-SKU', // Same as existing variant SKU
            ],
        ],
    ]);

    $failClosureCalled = false;

    $rule->validate('variants.0.sku', 'DUPLICATE-VARIANT-SKU', function () use (&$failClosureCalled) {
        $failClosureCalled = true;
    });

    expect($failClosureCalled)->toBeTrue();
});

test('validation fails when value exists in other variants in the same request', function () {
    $rule = new ProductVariantUniqueRule('sku', 'SKU');
    $rule->setData([
        'variants' => [
            [
                'id' => 'variant-1',
                'name' => 'Variant 1',
                'sku' => 'DUPLICATE-SKU',
            ],
            [
                'id' => 'variant-2',
                'name' => 'Variant 2',
                'sku' => 'DUPLICATE-SKU', // Same SKU as variant-1
            ],
        ],
    ]);

    $failClosureCalled = false;

    $rule->validate('variants.0.sku', 'DUPLICATE-SKU', function () use (&$failClosureCalled) {
        $failClosureCalled = true;
    });

    expect($failClosureCalled)->toBeTrue();
});
