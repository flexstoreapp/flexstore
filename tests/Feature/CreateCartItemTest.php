<?php

declare(strict_types=1);

use App\Actions\StoreCartItemAction;
use App\Http\Controllers\Storefront\CartItemController;
use App\Http\Requests\Storefront\StoreCartItemRequest;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\post;
use function Pest\Laravel\withUnencryptedCookie;

covers(CartItemController::class, StoreCartItemRequest::class, StoreCartItemAction::class);

uses()->group('cart');

test('adds item to cart', function () {
    $cart = Cart::factory()->create();
    $product = Product::factory()->create([
        'price' => '25.0000',
        'track_stock' => false,
        'in_stock' => true,
        'is_active' => true,
    ]);

    $response = withUnencryptedCookie('cart_id', $cart->id)->post(route('cart.items.store'), [
        'product_id' => $product->id,
        'quantity' => 2,
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('cart_items', [
        'cart_id' => $cart->id,
        'product_id' => $product->id,
        'quantity' => 2,
    ]);
});

test('updates existing item when same product added', function () {
    $cart = Cart::factory()->create();
    $product = Product::factory()->create([
        'price' => '25.0000',
        'track_stock' => false,
        'in_stock' => true,
        'is_active' => true,
    ]);

    CartItem::factory()->for($cart)->create([
        'product_id' => $product->id,
        'quantity' => 2,
        'unit_price' => '25.0000',
        'total_price' => '50.0000',
    ]);

    $response = withUnencryptedCookie('cart_id', $cart->id)->post(route('cart.items.store'), [
        'product_id' => $product->id,
        'quantity' => 3,
    ]);

    $response->assertRedirectBack();

    expect(CartItem::query()->where('cart_id', $cart->id)->count())->toBe(1)
        ->and(CartItem::query()->where('cart_id', $cart->id)->first()->quantity)->toBe(5);
});

test('buy now adds item and redirects to checkout', function () {
    $cart = Cart::factory()->create();
    $product = Product::factory()->create([
        'price' => '25.0000',
        'track_stock' => false,
        'in_stock' => true,
        'is_active' => true,
    ]);

    $response = withUnencryptedCookie('cart_id', $cart->id)->post(route('cart.items.store'), [
        'product_id' => $product->id,
        'quantity' => 2,
        'buy_now' => true,
    ]);

    $response->assertRedirect(route('checkout.create'))
        ->assertSessionHasNoErrors();

    assertDatabaseHas('cart_items', [
        'cart_id' => $cart->id,
        'product_id' => $product->id,
        'quantity' => 2,
    ]);
});

test('buy now redirects back without checkout when validation fails', function () {
    $cart = Cart::factory()->create();
    $product = Product::factory()->create([
        'price' => '25.0000',
        'track_stock' => false,
        'in_stock' => false,
        'is_active' => true,
    ]);

    $response = withUnencryptedCookie('cart_id', $cart->id)->post(route('cart.items.store'), [
        'product_id' => $product->id,
        'quantity' => 1,
        'buy_now' => true,
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('quantity');

    expect(CartItem::query()->where('cart_id', $cart->id)->count())->toBe(0);
});

test('validates required fields when adding item', function () {
    $response = post(route('cart.items.store'), []);

    $response->assertRedirectBack()
        ->assertInvalid(['product_id', 'quantity']);
});

test('validates product exists when adding item', function () {
    $response = post(route('cart.items.store'), [
        'product_id' => 99999,
        'quantity' => 1,
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('product_id');
});

test('validates quantity is positive when adding item', function () {
    $product = Product::factory()->active()->create();

    $response = post(route('cart.items.store'), [
        'product_id' => $product->id,
        'quantity' => 0,
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('quantity');
});

test('adds item with product variant', function () {
    $cart = Cart::factory()->create();
    $product = Product::factory()->create([
        'price' => '20.0000',
        'in_stock' => true,
        'is_active' => true,
    ]);
    $variant = ProductVariant::factory()->for($product)->create([
        'price' => '30.0000',
        'track_stock' => false,
        'in_stock' => true,
    ]);

    $response = withUnencryptedCookie('cart_id', $cart->id)->post(route('cart.items.store'), [
        'product_id' => $product->id,
        'product_variant_id' => $variant->id,
        'quantity' => 1,
    ]);

    $response->assertRedirectBack();

    assertDatabaseHas('cart_items', [
        'cart_id' => $cart->id,
        'product_id' => $product->id,
        'product_variant_id' => $variant->id,
    ]);
});

test('creates new cart when adding item without cart cookie', function () {
    $product = Product::factory()->create([
        'price' => '25.0000',
        'track_stock' => false,
        'in_stock' => true,
        'is_active' => true,
    ]);

    $response = post(route('cart.items.store'), [
        'product_id' => $product->id,
        'quantity' => 1,
    ]);

    $response->assertRedirectBack();

    expect(Cart::query()->count())->toBe(1);
    expect(CartItem::query()->count())->toBe(1);
});

test('requires variant when product has variants', function () {
    $product = Product::factory()->create([
        'price' => '25.0000',
        'in_stock' => true,
        'is_active' => true,
    ]);
    ProductVariant::factory()->for($product)->create([
        'price' => '30.0000',
        'track_stock' => false,
        'in_stock' => true,
    ]);

    $response = post(route('cart.items.store'), [
        'product_id' => $product->id,
        'quantity' => 1,
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('product_variant_id');
});

test('validates variant belongs to product when adding with variant', function () {
    $product1 = Product::factory()->create(['is_active' => true, 'in_stock' => true]);
    $product2 = Product::factory()->create(['is_active' => true, 'in_stock' => true]);
    $variant = ProductVariant::factory()->for($product2)->create(['in_stock' => true]);

    $response = post(route('cart.items.store'), [
        'product_id' => $product1->id,
        'product_variant_id' => $variant->id,
        'quantity' => 1,
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('product_variant_id');
});

test('cannot add out of stock product to cart', function () {
    $cart = Cart::factory()->create();
    $product = Product::factory()->create([
        'price' => '25.0000',
        'track_stock' => false,
        'in_stock' => false,
        'is_active' => true,
    ]);

    $response = withUnencryptedCookie('cart_id', $cart->id)->post(route('cart.items.store'), [
        'product_id' => $product->id,
        'quantity' => 1,
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('quantity');

    expect(CartItem::query()->where('cart_id', $cart->id)->count())->toBe(0);
});

test('cannot add product variant that is out of stock', function () {
    $cart = Cart::factory()->create();
    $product = Product::factory()->create([
        'price' => '20.0000',
        'in_stock' => true,
        'is_active' => true,
    ]);
    $variant = ProductVariant::factory()->for($product)->create([
        'price' => '30.0000',
        'track_stock' => false,
        'in_stock' => false,
    ]);

    $response = withUnencryptedCookie('cart_id', $cart->id)->post(route('cart.items.store'), [
        'product_id' => $product->id,
        'product_variant_id' => $variant->id,
        'quantity' => 1,
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('quantity');

    expect(CartItem::query()->where('cart_id', $cart->id)->count())->toBe(0);
});

test('cannot add product when requested quantity exceeds stock', function () {
    $cart = Cart::factory()->create();
    $product = Product::factory()->create([
        'price' => '25.0000',
        'track_stock' => true,
        'stock' => 5,
        'in_stock' => true,
        'is_active' => true,
    ]);

    $response = withUnencryptedCookie('cart_id', $cart->id)->post(route('cart.items.store'), [
        'product_id' => $product->id,
        'quantity' => 10,
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('quantity');

    expect(CartItem::query()->where('cart_id', $cart->id)->count())->toBe(0);
});

test('cannot add more items when combined quantity exceeds stock', function () {
    $cart = Cart::factory()->create();
    $product = Product::factory()->create([
        'price' => '25.0000',
        'track_stock' => true,
        'stock' => 5,
        'in_stock' => true,
        'is_active' => true,
    ]);

    CartItem::factory()->for($cart)->create([
        'product_id' => $product->id,
        'quantity' => 3,
        'unit_price' => '25.0000',
        'total_price' => '75.0000',
    ]);

    $response = withUnencryptedCookie('cart_id', $cart->id)->post(route('cart.items.store'), [
        'product_id' => $product->id,
        'quantity' => 3,
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('quantity');

    expect(CartItem::query()->where('cart_id', $cart->id)->first()->quantity)->toBe(3);
});
