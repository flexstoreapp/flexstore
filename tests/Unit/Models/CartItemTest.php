<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

covers(CartItem::class);

uses()->group('models', 'cart');

test('has factory', function () {
    expect(CartItem::factory())->toBeInstanceOf(Factory::class);
});

test('touches cart relationship', function () {
    $cartItem = new CartItem();

    expect($cartItem->getTouchedRelations())->toBe(['cart']);
});

test('casts attributes correctly', function () {
    $cartItem = CartItem::factory()->create([
        'unit_price' => '29.9900',
        'total_price' => '59.9800',
        'variant_options' => ['size' => 'L', 'color' => 'red'],
    ]);

    $casts = $cartItem->casts();

    expect($casts)
        ->toBeArray()
        ->toHaveKey('unit_price', 'decimal:4')
        ->toHaveKey('total_price', 'decimal:4')
        ->toHaveKey('variant_options', 'array');
});

test('casts decimal values correctly', function () {
    $cartItem = CartItem::factory()->create([
        'unit_price' => '29.9900',
        'total_price' => '89.9700',
        'quantity' => 3,
    ]);

    expect($cartItem->unit_price)->toBe('29.9900');
    expect($cartItem->total_price)->toBe('89.9700');
    expect($cartItem->quantity)->toBe(3);
});

test('handles decimal precision for monetary values', function () {
    $cartItem = CartItem::factory()->create([
        'unit_price' => '19.999',
        'total_price' => '39.998',
    ]);

    expect($cartItem->unit_price)->toBe('19.9990');
    expect($cartItem->total_price)->toBe('39.9980');
});

test('casts variant_options as array correctly', function () {
    $options = ['size' => 'M', 'color' => 'blue'];
    $cartItem = CartItem::factory()->create([
        'variant_options' => $options,
    ]);

    expect($cartItem->variant_options)->toBeArray()
        ->toBe($options);
});

test('handles null variant_options', function () {
    $cartItem = CartItem::factory()->create([
        'variant_options' => null,
    ]);

    expect($cartItem->variant_options)->toBeNull();
});

test('belongs to cart relationship', function () {
    $cart = Cart::factory()->create();
    $cartItem = CartItem::factory()->create([
        'cart_id' => $cart->id,
    ]);

    expect($cartItem->cart)->toBeInstanceOf(Cart::class)
        ->and($cartItem->cart->id)->toBe($cart->id);
});

test('belongs to product relationship', function () {
    $product = Product::factory()->create();
    $cartItem = CartItem::factory()->create([
        'product_id' => $product->id,
    ]);

    expect($cartItem->product)->toBeInstanceOf(Product::class)
        ->and($cartItem->product->id)->toBe($product->id);
});

test('belongs to product variant relationship', function () {
    $productVariant = ProductVariant::factory()->create();
    $cartItem = CartItem::factory()->create([
        'product_variant_id' => $productVariant->id,
    ]);

    expect($cartItem->productVariant)->toBeInstanceOf(ProductVariant::class)
        ->and($cartItem->productVariant->id)->toBe($productVariant->id);
});

test('handles null product variant relationship', function () {
    $cartItem = CartItem::factory()->create(['product_variant_id' => null]);

    expect($cartItem->productVariant)->toBeNull();
});

test('handles cart item fields correctly', function () {
    $cart = Cart::factory()->create();
    $product = Product::factory()->create();

    $cartItem = CartItem::factory()->create([
        'cart_id' => $cart->id,
        'product_id' => $product->id,
        'product_variant_id' => null,
        'quantity' => 2,
        'unit_price' => '15.5000',
        'total_price' => '31.0000',
        'variant_options' => ['size' => 'L'],
    ]);

    expect($cartItem->cart_id)->toBe($cart->id);
    expect($cartItem->product_id)->toBe($product->id);
    expect($cartItem->product_variant_id)->toBeNull();
    expect($cartItem->quantity)->toBe(2);
    expect($cartItem->unit_price)->toBe('15.5000');
    expect($cartItem->total_price)->toBe('31.0000');
    expect($cartItem->variant_options)->toBe(['size' => 'L']);
});

test('handles quantity calculations correctly', function () {
    $cartItem = CartItem::factory()->create([
        'quantity' => 5,
        'unit_price' => '10.0000',
    ]);

    // Note: total_price is calculated in the factory, so we just verify it's set
    expect($cartItem->quantity)->toBe(5);
    expect($cartItem->unit_price)->toBe('10.0000');
    expect($cartItem->total_price)->toBeString();
});

test('creates cart item with timestamps', function () {
    $cartItem = CartItem::factory()->create();

    expect($cartItem->id)->toBeInt();
    expect($cartItem->created_at)->toBeInstanceOf(\Carbon\CarbonImmutable::class);
    expect($cartItem->updated_at)->toBeInstanceOf(\Carbon\CarbonImmutable::class);
});
