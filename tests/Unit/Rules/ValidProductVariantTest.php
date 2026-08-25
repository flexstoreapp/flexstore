<?php

declare(strict_types=1);

use App\Models\Product;
use App\Models\ProductVariant;
use App\Rules\ValidProductVariant;

covers(ValidProductVariant::class);

uses()->group('rules', 'product');

test('validation is skipped when value is blank', function () {
    $rule = new ValidProductVariant(1);

    $failClosureCalled = false;

    foreach ([null, '', '   '] as $value) {
        $rule->validate('items.0.product_variant_id', $value, function () use (&$failClosureCalled) {
            $failClosureCalled = true;
        });

        expect($failClosureCalled)->toBeFalse();
    }
});

test('validation passes when product variant exists and belongs to the specified product', function () {
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
    ]);

    $rule = new ValidProductVariant($product->id);

    $failClosureCalled = false;

    $rule->validate('items.0.product_variant_id', $variant->id, function () use (&$failClosureCalled) {
        $failClosureCalled = true;
    });

    expect($failClosureCalled)->toBeFalse();
});

test('validation works with different array indices', function () {
    $product1 = Product::factory()->create();
    $product2 = Product::factory()->create();

    $variant1 = ProductVariant::factory()->create([
        'product_id' => $product1->id,
    ]);
    $variant2 = ProductVariant::factory()->create([
        'product_id' => $product2->id,
    ]);

    $firstRule = new ValidProductVariant($product1->id);

    // Test first item - should pass
    $failClosureCalled = false;
    $firstRule->validate('items.0.product_variant_id', $variant1->id, function () use (&$failClosureCalled) {
        $failClosureCalled = true;
    });
    expect($failClosureCalled)->toBeFalse();

    $secondRule = new ValidProductVariant($product2->id);

    // Test second item - should pass
    $failClosureCalled = false;
    $secondRule->validate('items.1.product_variant_id', $variant2->id, function () use (&$failClosureCalled) {
        $failClosureCalled = true;
    });
    expect($failClosureCalled)->toBeFalse();

    // Test second item with wrong variant - should fail
    $failClosureCalled = false;
    $secondRule->validate('items.1.product_variant_id', $variant1->id, function () use (&$failClosureCalled) {
        $failClosureCalled = true;
    });
    expect($failClosureCalled)->toBeTrue();
});

test('validation fails when product variant does not exist', function () {
    $product = Product::factory()->create();

    $rule = new ValidProductVariant($product->id);

    $failClosureCalled = false;
    $errorMessage = '';

    $rule->validate('items.0.product_variant_id', '00000000-0000-0000-0000-000000000000', function ($message) use (&$failClosureCalled, &$errorMessage) {
        $failClosureCalled = true;
        $errorMessage = $message;
    });

    expect($failClosureCalled)->toBeTrue();
});

test('validation fails when product variant exists but does not belong to the specified product', function () {
    $product1 = Product::factory()->create();
    $product2 = Product::factory()->create();

    $variant = ProductVariant::factory()->create([
        'product_id' => $product1->id,
    ]);

    $rule = new ValidProductVariant($product2->id);

    $failClosureCalled = false;
    $errorMessage = '';

    $rule->validate('items.0.product_variant_id', $variant->id, function ($message) use (&$failClosureCalled, &$errorMessage) {
        $failClosureCalled = true;
        $errorMessage = $message;
    });

    expect($failClosureCalled)->toBeTrue();
});

test('validation handles missing product_id gracefully', function () {
    $variant = ProductVariant::factory()->create();

    $rule = new ValidProductVariant(null);

    $failClosureCalled = false;

    $rule->validate('items.0.product_variant_id', $variant->id, function () use (&$failClosureCalled) {
        $failClosureCalled = true;
    });

    expect($failClosureCalled)->toBeTrue();
});
