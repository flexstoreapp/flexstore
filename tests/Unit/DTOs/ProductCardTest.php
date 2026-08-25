<?php

declare(strict_types=1);

use App\DTOs\ProductCard;
use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductVariant;

covers(ProductCard::class);

uses()->group('dtos');

test('falls back to the product stock flag when variants are not loaded', function (): void {
    $product = Product::factory()->create(['in_stock' => true]);
    $product->setAttribute('variants_count', 3);

    expect(ProductCard::fromProduct($product)->toArray()['in_stock'])->toBeTrue();
});

test('derives stock from variants when the product has them', function (): void {
    $product = Product::factory()->create(['in_stock' => false]);
    $product->setAttribute('variants_count', 2);
    $product->setRelation('variants', collect([
        new ProductVariant(['in_stock' => false]),
        new ProductVariant(['in_stock' => true]),
    ]));

    $card = ProductCard::fromProduct($product)->toArray();

    expect($card['has_variants'])->toBeTrue()
        ->and($card['in_stock'])->toBeTrue();
});

test('reports out of stock when no variant is in stock', function (): void {
    $product = Product::factory()->create(['in_stock' => true]);
    $product->setAttribute('variants_count', 1);
    $product->setRelation('variants', collect([new ProductVariant(['in_stock' => false])]));

    expect(ProductCard::fromProduct($product)->toArray()['in_stock'])->toBeFalse();
});

test('includes the brand name and handle when the product has a brand', function (): void {
    $brand = Brand::factory()->create(['name' => ['en' => 'Acme'], 'url_handle' => 'acme']);
    $product = Product::factory()->for($brand)->create();
    $product->setAttribute('variants_count', 0);
    $product->setRelation('brand', $brand);

    $card = ProductCard::fromProduct($product)->toArray();

    expect($card['brand'])->toBe(['name' => ['en' => 'Acme'], 'url_handle' => 'acme']);
});

test('always includes created_at', function (): void {
    $product = Product::factory()->create();
    $product->setAttribute('variants_count', 0);

    $card = ProductCard::fromProduct($product)->toArray();

    expect($card)->toHaveKey('created_at')
        ->and($card['created_at'])->toBe($product->created_at->toISOString());
});
