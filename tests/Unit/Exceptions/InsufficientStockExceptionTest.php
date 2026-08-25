<?php

declare(strict_types=1);

use App\Exceptions\InsufficientStockException;
use App\Models\Product;
use App\Models\ProductVariant;

covers(InsufficientStockException::class);

uses()->group('exceptions');

test('message includes product title and stock numbers', function () {
    $product = Product::factory()->create(['title' => ['en' => 'Red Shirt']]);

    $exception = new InsufficientStockException(
        product: $product,
        variant: null,
        requested: 10,
        available: 3,
    );

    expect($exception->getMessage())->toContain('Red Shirt')
        ->and($exception->getMessage())->toContain('Requested: 10')
        ->and($exception->getMessage())->toContain('Available: 3')
        ->and($exception->product->id)->toBe($product->id)
        ->and($exception->variant)->toBeNull()
        ->and($exception->requested)->toBe(10)
        ->and($exception->available)->toBe(3);
});

test('message includes variant title when variant is present', function () {
    $product = Product::factory()->create(['title' => ['en' => 'T-Shirt']]);
    $variant = ProductVariant::factory()->for($product)->create(['title' => 'L / Red']);

    $exception = new InsufficientStockException(
        product: $product,
        variant: $variant,
        requested: 5,
        available: 2,
    );

    expect($exception->getMessage())->toContain('T-Shirt (L / Red)')
        ->and($exception->variant->id)->toBe($variant->id);
});
