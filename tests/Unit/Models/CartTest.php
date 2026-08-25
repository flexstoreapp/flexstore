<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\WeightUnit;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\PaymentGateway;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingRate;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

covers(Cart::class);

uses()->group('models', 'cart');

test('has factory', function () {
    expect(Cart::factory())->toBeInstanceOf(Factory::class);
});

test('uses non-incrementing string key', function () {
    $cart = new Cart();

    expect($cart->getIncrementing())->toBeFalse()
        ->and($cart->getKeyType())->toBe('string');
});

test('uses HasUuids trait', function () {
    expect(Cart::class)->toUseTrait(HasUuids::class);
});

test('generates a unique version 7 uuid as id on create', function () {
    $first = Cart::factory()->create();
    $second = Cart::factory()->create();

    expect($first->id)->toBeString()
        ->and(Str::isUuid($first->id, 7))->toBeTrue()
        ->and($first->id)->not->toBe($second->id);
});

test('keeps an explicitly provided id', function () {
    $id = Str::uuid7()->toString();

    expect(Cart::factory()->create(['id' => $id])->id)->toBe($id);
});

test('casts attributes correctly', function () {
    $cart = new Cart();
    $casts = $cart->casts();

    expect($casts)
        ->toBeArray()
        ->toHaveKey('subtotal', 'decimal:4')
        ->toHaveKey('tax_total', 'decimal:4')
        ->toHaveKey('shipping_total', 'decimal:4')
        ->toHaveKey('discount_total', 'decimal:4')
        ->toHaveKey('total', 'decimal:4');
});

test('has items relationship', function () {
    $cart = Cart::factory()->create();
    $cartItem = CartItem::factory()->for($cart)->create();

    expect($cart->items())->toBeInstanceOf(HasMany::class)
        ->and($cart->items()->getRelated())->toBeInstanceOf(CartItem::class)
        ->and($cart->items)->toBeInstanceOf(Collection::class)
        ->and($cart->items->first()->id)->toBe($cartItem->id);
});

test('has customer relationship', function () {
    $user = User::factory()->create();
    $cart = Cart::factory()->create([
        'customer_id' => $user->id,
    ]);

    expect($cart->customer())->toBeInstanceOf(BelongsTo::class)
        ->and($cart->customer)->toBeInstanceOf(User::class)
        ->and($cart->customer->id)->toBe($user->id);
});

test('has shipping rate relationship', function () {
    $shippingRate = ShippingRate::factory()->create();
    $cart = Cart::factory()->create([
        'shipping_rate_id' => $shippingRate->id,
    ]);

    expect($cart->shippingRate())->toBeInstanceOf(BelongsTo::class)
        ->and($cart->shippingRate)->toBeInstanceOf(ShippingRate::class)
        ->and($cart->shippingRate->id)->toBe($shippingRate->id);
});

test('has payment gateway relationship', function () {
    $paymentGateway = PaymentGateway::factory()->create();
    $cart = Cart::factory()->create([
        'payment_gateway_id' => $paymentGateway->id,
    ]);

    expect($cart->paymentGateway())->toBeInstanceOf(BelongsTo::class)
        ->and($cart->paymentGateway)->toBeInstanceOf(PaymentGateway::class)
        ->and($cart->paymentGateway->id)->toBe($paymentGateway->id);
});

test('handles null customer relationship', function () {
    $cart = Cart::factory()->create(['customer_id' => null]);

    expect($cart->customer)->toBeNull();
});

test('handles null shipping rate relationship', function () {
    $cart = Cart::factory()->create(['shipping_rate_id' => null]);

    expect($cart->shippingRate)->toBeNull();
});

test('handles null payment gateway relationship', function () {
    $cart = Cart::factory()->create(['payment_gateway_id' => null]);

    expect($cart->paymentGateway)->toBeNull();
});

test('calculates weight in grams for single product', function () {
    $product = Product::factory()->create([
        'weight' => '1.00',
        'weight_unit' => WeightUnit::Kg,
    ]);
    $cart = Cart::factory()->create();
    CartItem::factory()->for($cart)->create([
        'product_id' => $product->id,
        'quantity' => 1,
    ]);

    $weightInGrams = $cart->weight_in_grams;

    expect($weightInGrams)->toBeString()
        ->and($weightInGrams)->toBe('1000.00');
});

test('calculates weight in grams with quantity', function () {
    $product = Product::factory()->create([
        'weight' => '0.5',
        'weight_unit' => WeightUnit::Kg,
    ]);
    $cart = Cart::factory()->create();
    CartItem::factory()->for($cart)->create([
        'product_id' => $product->id,
        'quantity' => 3,
    ]);

    $weightInGrams = $cart->weight_in_grams;

    expect($weightInGrams)->toBeString()
        ->and($weightInGrams)->toBe('1500.00');
});

test('calculates weight correctly with multiple items', function () {
    $product1 = Product::factory()->create([
        'weight' => '0.5',
        'weight_unit' => WeightUnit::Kg,
    ]);
    $product2 = Product::factory()->create([
        'weight' => '0.3',
        'weight_unit' => WeightUnit::Kg,
    ]);
    $cart = Cart::factory()->create();
    CartItem::factory()->for($cart)->create([
        'product_id' => $product1->id,
        'quantity' => 2,
    ]);
    CartItem::factory()->for($cart)->create([
        'product_id' => $product2->id,
        'quantity' => 1,
    ]);

    $weightInGrams = $cart->weight_in_grams;

    expect($weightInGrams)->toBeString()
        ->and($weightInGrams)->toBe('1300.00');
});

test('handles weight unit conversions correctly', function () {
    $product1 = Product::factory()->create([
        'weight' => '1000',
        'weight_unit' => WeightUnit::G,
    ]);
    $product2 = Product::factory()->create([
        'weight' => '1',
        'weight_unit' => WeightUnit::Lb,
    ]);
    $product3 = Product::factory()->create([
        'weight' => '16',
        'weight_unit' => WeightUnit::Oz,
    ]);
    $cart = Cart::factory()->create();
    CartItem::factory()->for($cart)->create([
        'product_id' => $product1->id,
        'quantity' => 1,
    ]);
    CartItem::factory()->for($cart)->create([
        'product_id' => $product2->id,
        'quantity' => 1,
    ]);
    CartItem::factory()->for($cart)->create([
        'product_id' => $product3->id,
        'quantity' => 1,
    ]);

    $weightInGrams = $cart->weight_in_grams;

    expect($weightInGrams)->toBeString()
        ->and($weightInGrams)->toBe('1907.18');
});

test('uses variant weight when product variant exists', function () {
    $product = Product::factory()->create([
        'weight' => '2.00',
        'weight_unit' => WeightUnit::Kg,
    ]);
    $variant = ProductVariant::factory()->for($product)->create([
        'weight' => '1.00',
        'weight_unit' => WeightUnit::Kg,
    ]);
    $cart = Cart::factory()->create();
    CartItem::factory()->for($cart)->create([
        'product_id' => $product->id,
        'product_variant_id' => $variant->id,
        'quantity' => 1,
    ]);

    $weightInGrams = $cart->weight_in_grams;

    expect($weightInGrams)->toBeString()
        ->and($weightInGrams)->toBe('1000.00');
});

test('returns zero when cart has no items', function () {
    $cart = Cart::factory()->create();

    $weightInGrams = $cart->weight_in_grams;

    expect($weightInGrams)->toBeString()
        ->and($weightInGrams)->toBe('0.00');
});

test('handles products without weight', function () {
    $product = Product::factory()->create([

        'weight' => null,
        'weight_unit' => null,
    ]);
    $cart = Cart::factory()->create();
    CartItem::factory()->for($cart)->create([
        'product_id' => $product->id,
        'quantity' => 1,
    ]);

    $weightInGrams = $cart->weight_in_grams;

    expect($weightInGrams)->toBeString()
        ->and($weightInGrams)->toBe('0.00');
});

test('handles mixed products with and without weight', function () {
    $productWithWeight = Product::factory()->create([
        'weight' => '1.00',
        'weight_unit' => WeightUnit::Kg,
    ]);
    $productWithoutWeight = Product::factory()->create([

        'weight' => null,
        'weight_unit' => null,
    ]);
    $cart = Cart::factory()->create();
    CartItem::factory()->for($cart)->create([
        'product_id' => $productWithWeight->id,
        'quantity' => 1,
    ]);
    CartItem::factory()->for($cart)->create([
        'product_id' => $productWithoutWeight->id,
        'quantity' => 1,
    ]);

    $weightInGrams = $cart->weight_in_grams;

    expect($weightInGrams)->toBeString()
        ->and($weightInGrams)->toBe('1000.00');
});

test('calculates weight correctly with variant quantity', function () {
    $product = Product::factory()->create([
        'weight' => '2.00',
        'weight_unit' => WeightUnit::Kg,
    ]);
    $variant = ProductVariant::factory()->for($product)->create([
        'weight' => '0.5',
        'weight_unit' => WeightUnit::Kg,
    ]);
    $cart = Cart::factory()->create();
    CartItem::factory()->for($cart)->create([
        'product_id' => $product->id,
        'product_variant_id' => $variant->id,
        'quantity' => 4,
    ]);

    $weightInGrams = $cart->weight_in_grams;

    expect($weightInGrams)->toBeString()
        ->and($weightInGrams)->toBe('2000.00');
});

test('handles different weight units in same cart', function () {
    $product1 = Product::factory()->create([
        'weight' => '1',
        'weight_unit' => WeightUnit::Kg,
    ]);
    $product2 = Product::factory()->create([
        'weight' => '500',
        'weight_unit' => WeightUnit::G,
    ]);
    $product3 = Product::factory()->create([
        'weight' => '2',
        'weight_unit' => WeightUnit::Lb,
    ]);
    $cart = Cart::factory()->create();
    CartItem::factory()->for($cart)->create([
        'product_id' => $product1->id,
        'quantity' => 1,
    ]);
    CartItem::factory()->for($cart)->create([
        'product_id' => $product2->id,
        'quantity' => 1,
    ]);
    CartItem::factory()->for($cart)->create([
        'product_id' => $product3->id,
        'quantity' => 1,
    ]);

    $weightInGrams = $cart->weight_in_grams;

    expect($weightInGrams)->toBeString()
        ->and($weightInGrams)->toBe('2407.18');
});
