<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\CartController;
use App\Http\Controllers\Api\V1\CartItemController;
use App\Http\Requests\Api\V1\StoreCartItemRequest;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Media;
use App\Models\Product;
use App\Models\ProductVariant;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\patchJson;
use function Pest\Laravel\postJson;

covers(CartController::class, CartItemController::class, StoreCartItemRequest::class);

uses()->group('api');

test('the cart token returned on first use identifies the same cart next time', function (): void {
    $product = Product::factory()->inStock()->create(['is_active' => true, 'price' => '25.00', 'in_stock' => true]);

    $response = postJson(route('api.v1.cart.items.store'), [
        'product_id' => $product->id,
        'quantity' => 2,
    ]);

    $response->assertOk()
        ->assertJsonPath('data.item_count', 2)
        ->assertJsonPath('data.items.0.product_id', $product->id)
        ->assertJsonStructure(['data' => ['token', 'items', 'subtotal', 'total', 'requires_shipping']]);

    $token = $response->json('data.token');

    getJson(route('api.v1.cart.show'), ['X-Cart-Token' => $token])
        ->assertOk()
        ->assertJsonPath('data.token', $token)
        ->assertJsonPath('data.item_count', 2);
});

test('the cart token header resolves an existing cart', function (): void {
    $cart = Cart::factory()->create();
    $product = Product::factory()->inStock()->create(['is_active' => true, 'in_stock' => true]);
    CartItem::factory()->create(['cart_id' => $cart->id, 'product_id' => $product->id, 'quantity' => 3]);

    getJson(route('api.v1.cart.show'), ['X-Cart-Token' => $cart->id])
        ->assertOk()
        ->assertJsonPath('data.token', $cart->id)
        ->assertJsonPath('data.item_count', 3);
});

test('a cart item quantity can be updated', function (): void {
    $cart = Cart::factory()->create();
    $product = Product::factory()->inStock()->create(['is_active' => true, 'in_stock' => true, 'track_stock' => false]);
    $item = CartItem::factory()->create(['cart_id' => $cart->id, 'product_id' => $product->id, 'quantity' => 1]);

    patchJson(route('api.v1.cart.items.update', $item->id), ['quantity' => 4], ['X-Cart-Token' => $cart->id])
        ->assertOk()
        ->assertJsonPath('data.item_count', 4);
});

test('a cart item can be removed', function (): void {
    $cart = Cart::factory()->create();
    $product = Product::factory()->inStock()->create(['is_active' => true, 'in_stock' => true]);
    $item = CartItem::factory()->create(['cart_id' => $cart->id, 'product_id' => $product->id]);

    deleteJson(route('api.v1.cart.items.destroy', $item->id), [], ['X-Cart-Token' => $cart->id])
        ->assertOk()
        ->assertJsonCount(0, 'data.items');
});

test('an item belonging to another cart cannot be modified', function (): void {
    $cart = Cart::factory()->create();
    $other = Cart::factory()->create();
    $item = CartItem::factory()->create(['cart_id' => $other->id]);

    deleteJson(route('api.v1.cart.items.destroy', $item->id), [], ['X-Cart-Token' => $cart->id])
        ->assertJsonValidationErrors('item');

    assertDatabaseHas('cart_items', ['id' => $item->id, 'cart_id' => $other->id]);
});

test('the cart can be cleared', function (): void {
    $cart = Cart::factory()->create();
    CartItem::factory()->count(2)->create(['cart_id' => $cart->id]);

    deleteJson(route('api.v1.cart.destroy'), [], ['X-Cart-Token' => $cart->id])
        ->assertOk()
        ->assertJsonCount(0, 'data.items');
});

test('adding more than the available stock fails validation', function (): void {
    $product = Product::factory()->create([
        'is_active' => true,
        'in_stock' => true,
        'track_stock' => true,
        'stock' => 1,
    ]);

    postJson(route('api.v1.cart.items.store'), [
        'product_id' => $product->id,
        'quantity' => 5,
    ])->assertUnprocessable();
});

test('a variant line carries its options and the variant image', function (): void {
    $product = Product::factory()->available()->create();
    $variant = ProductVariant::factory()->for($product)->create([
        'in_stock' => true,
        'track_stock' => false,
        'media_id' => Media::factory()->create()->id,
    ]);

    $cart = Cart::factory()->create();
    CartItem::factory()->create([
        'cart_id' => $cart->id,
        'product_id' => $product->id,
        'product_variant_id' => $variant->id,
        'variant_options' => ['Size' => 'Medium'],
    ]);

    getJson(route('api.v1.cart.show'), ['X-Cart-Token' => $cart->id])
        ->assertOk()
        ->assertJsonPath('data.items.0.variant_options', ['Size' => 'Medium'])
        ->assertJsonPath('data.items.0.product_variant_id', $variant->id)
        ->assertJsonPath('data.items.0.featured_media.id', $variant->media_id);
});
