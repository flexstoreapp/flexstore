<?php

declare(strict_types=1);

use App\DTOs\HydratedOrderItem;
use App\Enums\WeightUnit;
use App\Models\Product;
use App\Models\ProductVariant;

covers(HydratedOrderItem::class);

uses()->group('dtos');

test('stores all properties with variant', function () {
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->for($product)->create(['price' => '75.0000']);

    $item = new HydratedOrderItem(
        product: $product,
        variant: $variant,
        unitPrice: '75.0000',
        totalPrice: '150.0000',
        quantity: 2,
        variantOptions: ['size' => 'L', 'color' => 'red'],
        productTitle: ['en' => 'T-Shirt'],
        productSku: 'VAR-001',
        variantTitle: 'L / Red',
        requiresShipping: true,
        weight: '0.5',
        weightUnit: WeightUnit::Kg,
    );

    expect($item->variant->id)->toBe($variant->id)
        ->and($item->variantOptions)->toBe(['size' => 'L', 'color' => 'red'])
        ->and($item->variantTitle)->toBe('L / Red')
        ->and($item->unitPrice)->toBe('75.0000')
        ->and($item->totalPrice)->toBe('150.0000')
        ->and($item->quantity)->toBe(2);
});

test('toArray includes variant fields when variant is set', function () {
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->for($product)->create();

    $item = new HydratedOrderItem(
        product: $product,
        variant: $variant,
        unitPrice: '75.0000',
        totalPrice: '75.0000',
        quantity: 1,
        variantOptions: ['size' => 'M', 'color' => 'blue'],
        productTitle: ['en' => 'T-Shirt'],
        productSku: 'BASE-SKU',
        variantTitle: 'M / Blue',
        requiresShipping: true,
        weight: '0.3',
        weightUnit: WeightUnit::Kg,
    );

    $array = $item->toArray();

    expect($array['variant_title'])->toBe('M / Blue')
        ->and($array['variant_options'])->toBe(['size' => 'M', 'color' => 'blue']);
});

test('properties are readonly', function () {
    $product = Product::factory()->create();

    $item = new HydratedOrderItem(
        product: $product,
        variant: null,
        unitPrice: '100.0000',
        totalPrice: '100.0000',
        quantity: 1,
        variantOptions: null,
        productTitle: ['en' => 'Product'],
        productSku: 'PROD-001',
        variantTitle: null,
        requiresShipping: true,
        weight: '1.0',
        weightUnit: WeightUnit::Kg,
    );

    expect(fn () => $item->quantity = 2)->toThrow(Error::class)
        ->and(fn () => $item->unitPrice = '200.0000')->toThrow(Error::class)
        ->and(fn () => $item->totalPrice = '200.0000')->toThrow(Error::class)
        ->and(fn () => $item->requiresShipping = false)->toThrow(Error::class);
});
