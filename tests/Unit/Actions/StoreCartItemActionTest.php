<?php

declare(strict_types=1);

use App\Actions\StoreCartItemAction;
use App\DTOs\StoreCartItemInput;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

use function Pest\Laravel\assertDatabaseHas;

covers(StoreCartItemAction::class, StoreCartItemInput::class);

uses()->group('actions', 'cart');

test('adds a new item to cart', function () {
    $cart = Cart::factory()->create();
    $product = Product::factory()->create([
        'price' => '25.0000',
        'in_stock' => true,
        'track_stock' => false,
        'is_active' => true,
    ]);

    $action = app(StoreCartItemAction::class);
    $result = $action->handle($cart->id, StoreCartItemInput::fromArray([
        'product_id' => $product->id,
        'quantity' => 2,
    ]));

    expect($result)->toBeInstanceOf(Cart::class)
        ->and($result->items)->toHaveCount(1)
        ->and($result->subtotal)->toBe('50.0000');

    assertDatabaseHas('cart_items', [
        'cart_id' => $cart->id,
        'product_id' => $product->id,
        'quantity' => 2,
        'unit_price' => '25.0000',
        'total_price' => '50.0000',
    ]);
});

test('updates existing item quantity when same product added', function () {
    $cart = Cart::factory()->create();
    $product = Product::factory()->create([
        'price' => '10.0000',
        'in_stock' => true,
        'track_stock' => false,
        'is_active' => true,
    ]);

    $existingItem = CartItem::factory()->for($cart)->create([
        'product_id' => $product->id,
        'quantity' => 2,
        'unit_price' => '10.0000',
        'total_price' => '20.0000',
    ]);

    $action = app(StoreCartItemAction::class);
    $result = $action->handle($cart->id, StoreCartItemInput::fromArray([
        'product_id' => $product->id,
        'quantity' => 3,
    ]));

    expect($result->items)->toHaveCount(1)
        ->and($result->subtotal)->toBe('50.0000');

    $existingItem->refresh();
    expect($existingItem->quantity)->toBe(5)
        ->and($existingItem->total_price)->toBe('50.0000');
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
        'in_stock' => true,
        'track_stock' => false,
    ]);

    $action = app(StoreCartItemAction::class);
    $result = $action->handle($cart->id, StoreCartItemInput::fromArray([
        'product_id' => $product->id,
        'product_variant_id' => $variant->id,
        'quantity' => 1,
    ]));

    expect($result->items)->toHaveCount(1)
        ->and($result->subtotal)->toBe('30.0000');

    assertDatabaseHas('cart_items', [
        'cart_id' => $cart->id,
        'product_id' => $product->id,
        'product_variant_id' => $variant->id,
        'quantity' => 1,
        'unit_price' => '30.0000',
        'total_price' => '30.0000',
    ]);
});

test('uses variant price over product price', function () {
    $cart = Cart::factory()->create();
    $product = Product::factory()->create([
        'price' => '15.0000',
        'in_stock' => true,
        'is_active' => true,
    ]);
    $variant = ProductVariant::factory()->for($product)->create([
        'price' => '25.0000',
        'in_stock' => true,
        'track_stock' => false,
    ]);

    $action = app(StoreCartItemAction::class);
    $result = $action->handle($cart->id, StoreCartItemInput::fromArray([
        'product_id' => $product->id,
        'product_variant_id' => $variant->id,
        'quantity' => 2,
    ]));

    $item = $result->items->first();
    expect($item->unit_price)->toBe('25.0000')
        ->and($item->total_price)->toBe('50.0000');
});

test('throws exception when product is inactive', function () {
    $cart = Cart::factory()->create();
    $product = Product::factory()->create([
        'is_active' => false,
    ]);

    $action = app(StoreCartItemAction::class);

    expect(fn () => $action->handle($cart->id, StoreCartItemInput::fromArray([
        'product_id' => $product->id,
        'quantity' => 1,
    ])))->toThrow(ModelNotFoundException::class);
});

test('throws exception when product does not exist', function () {
    $cart = Cart::factory()->create();

    $action = app(StoreCartItemAction::class);

    expect(fn () => $action->handle($cart->id, StoreCartItemInput::fromArray([
        'product_id' => 99999,
        'quantity' => 1,
    ])))->toThrow(ModelNotFoundException::class);
});

test('throws exception when stock is insufficient', function () {
    $cart = Cart::factory()->create();
    $product = Product::factory()->create([
        'price' => '10.0000',
        'in_stock' => true,
        'track_stock' => true,
        'stock' => 5,
        'is_active' => true,
    ]);

    $action = app(StoreCartItemAction::class);

    expect(fn () => $action->handle($cart->id, StoreCartItemInput::fromArray([
        'product_id' => $product->id,
        'quantity' => 10,
    ])))->toThrow(ValidationException::class);
});

test('allows adding when stock tracking is disabled', function () {
    $cart = Cart::factory()->create();
    $product = Product::factory()->create([
        'price' => '10.0000',
        'in_stock' => true,
        'track_stock' => false,
        'stock' => 0,
        'is_active' => true,
    ]);

    $action = app(StoreCartItemAction::class);
    $result = $action->handle($cart->id, StoreCartItemInput::fromArray([
        'product_id' => $product->id,
        'quantity' => 100,
    ]));

    expect($result->items)->toHaveCount(1);
});

test('checks stock for variant when variant has stock tracking', function () {
    $cart = Cart::factory()->create();
    $product = Product::factory()->create([
        'price' => '10.0000',
        'in_stock' => true,
        'track_stock' => false,
        'is_active' => true,
    ]);
    $variant = ProductVariant::factory()->for($product)->create([
        'price' => '20.0000',
        'in_stock' => true,
        'track_stock' => true,
        'stock' => 3,
    ]);

    $action = app(StoreCartItemAction::class);

    expect(fn () => $action->handle($cart->id, StoreCartItemInput::fromArray([
        'product_id' => $product->id,
        'product_variant_id' => $variant->id,
        'quantity' => 5,
    ])))->toThrow(ValidationException::class);
});

test('allows adding when variant stock is sufficient', function () {
    $cart = Cart::factory()->create();
    $product = Product::factory()->create([
        'price' => '10.0000',
        'in_stock' => true,
        'track_stock' => false,
        'is_active' => true,
    ]);
    $variant = ProductVariant::factory()->for($product)->create([
        'price' => '20.0000',
        'in_stock' => true,
        'track_stock' => true,
        'stock' => 10,
    ]);

    $action = app(StoreCartItemAction::class);
    $result = $action->handle($cart->id, StoreCartItemInput::fromArray([
        'product_id' => $product->id,
        'product_variant_id' => $variant->id,
        'quantity' => 5,
    ]));

    expect($result->items)->toHaveCount(1);
});

test('combines quantities when adding same variant', function () {
    $cart = Cart::factory()->create();
    $product = Product::factory()->create([
        'price' => '10.0000',
        'in_stock' => true,
        'track_stock' => false,
        'is_active' => true,
    ]);
    $variant = ProductVariant::factory()->for($product)->create([
        'price' => '20.0000',
        'in_stock' => true,
        'track_stock' => true,
        'stock' => 10,
    ]);

    CartItem::factory()->for($cart)->create([
        'product_id' => $product->id,
        'product_variant_id' => $variant->id,
        'quantity' => 2,
        'unit_price' => '20.0000',
        'total_price' => '40.0000',
    ]);

    $action = app(StoreCartItemAction::class);
    $result = $action->handle($cart->id, StoreCartItemInput::fromArray([
        'product_id' => $product->id,
        'product_variant_id' => $variant->id,
        'quantity' => 3,
    ]));

    expect($result->items)->toHaveCount(1)
        ->and($result->items->first()->quantity)->toBe(5);
});

test('ensures minimum quantity of 1', function () {
    $cart = Cart::factory()->create();
    $product = Product::factory()->create([
        'price' => '10.0000',
        'in_stock' => true,
        'track_stock' => false,
        'is_active' => true,
    ]);

    $action = app(StoreCartItemAction::class);
    $result = $action->handle($cart->id, StoreCartItemInput::fromArray([
        'product_id' => $product->id,
        'quantity' => 0,
    ]));

    expect($result->items->first()->quantity)->toBe(1);
});

test('saves variant options when provided', function () {
    $cart = Cart::factory()->create();
    $product = Product::factory()->create([
        'price' => '10.0000',
        'in_stock' => true,
        'track_stock' => false,
        'is_active' => true,
    ]);

    $variantOptions = [
        'Size' => 'Large',
        'Color' => 'Blue',
    ];

    $action = app(StoreCartItemAction::class);
    $result = $action->handle($cart->id, StoreCartItemInput::fromArray([
        'product_id' => $product->id,
        'quantity' => 1,
        'variant_options' => $variantOptions,
    ]));

    expect($result->items->first()->variant_options)->toBe($variantOptions);
});

test('throws exception when variant does not belong to product', function () {
    $cart = Cart::factory()->create();
    $product1 = Product::factory()->active()->create();
    $product2 = Product::factory()->active()->create();
    $variant = ProductVariant::factory()->for($product2)->create();

    $action = app(StoreCartItemAction::class);

    expect(fn () => $action->handle($cart->id, StoreCartItemInput::fromArray([
        'product_id' => $product1->id,
        'product_variant_id' => $variant->id,
        'quantity' => 1,
    ])))->toThrow(ModelNotFoundException::class);
});

test('refreshes cart totals after adding item', function () {
    $cart = Cart::factory()->create([
        'subtotal' => '0.0000',
        'total' => '0.0000',
    ]);
    $product = Product::factory()->create([
        'price' => '15.5000',
        'in_stock' => true,
        'track_stock' => false,
        'is_active' => true,
    ]);

    $action = app(StoreCartItemAction::class);
    $result = $action->handle($cart->id, StoreCartItemInput::fromArray([
        'product_id' => $product->id,
        'quantity' => 2,
    ]));

    expect($result->subtotal)->toBe('31.0000')
        ->and($result->total)->toBe('31.0000');
});

test('snapshots the compare at price when the product is discounted', function () {
    $cart = Cart::factory()->create();
    $product = Product::factory()->create([
        'price' => '25.0000',
        'compare_at_price' => '40.0000',
        'in_stock' => true,
        'track_stock' => false,
        'is_active' => true,
    ]);

    app(StoreCartItemAction::class)->handle($cart->id, new StoreCartItemInput($product->id, null, 1, null));

    assertDatabaseHas('cart_items', [
        'product_id' => $product->id,
        'unit_price' => '25.0000',
        'compare_at_price' => '40.0000',
    ]);
});

test('prefers the variant compare at price over the product one', function () {
    $cart = Cart::factory()->create();
    $product = Product::factory()->create([
        'price' => '25.0000',
        'compare_at_price' => '40.0000',
        'in_stock' => true,
        'track_stock' => false,
        'is_active' => true,
    ]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price' => '30.0000',
        'compare_at_price' => '55.0000',
        'in_stock' => true,
        'track_stock' => false,
    ]);

    app(StoreCartItemAction::class)->handle($cart->id, new StoreCartItemInput($product->id, $variant->id, 1, null));

    assertDatabaseHas('cart_items', [
        'product_variant_id' => $variant->id,
        'unit_price' => '30.0000',
        'compare_at_price' => '55.0000',
    ]);
});

test('stores no compare at price when it does not beat the unit price', function () {
    $cart = Cart::factory()->create();
    $product = Product::factory()->create([
        'price' => '25.0000',
        'compare_at_price' => '25.0000',
        'in_stock' => true,
        'track_stock' => false,
        'is_active' => true,
    ]);

    app(StoreCartItemAction::class)->handle($cart->id, new StoreCartItemInput($product->id, null, 1, null));

    assertDatabaseHas('cart_items', [
        'product_id' => $product->id,
        'compare_at_price' => null,
    ]);
});
