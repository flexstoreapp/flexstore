<?php

declare(strict_types=1);

use App\Http\Controllers\Storefront\CartController;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\withUnencryptedCookie;

covers(CartController::class);

uses()->group('cart');

test('displays cart page', function () {
    $cart = Cart::factory()->create();
    $product = Product::factory()->create();
    CartItem::factory()->for($cart)->create([
        'product_id' => $product->id,
        'quantity' => 2,
        'unit_price' => '25.0000',
        'total_price' => '50.0000',
    ]);

    $response = withUnencryptedCookie('cart_id', $cart->id)->get(route('cart.show'));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('storefront/cart/show')
                ->has('cart')
                ->has('cart.items', 1)
        );
});

test('passes tax display settings to the cart page', function () {
    $cart = Cart::factory()->create();

    $response = withUnencryptedCookie('cart_id', $cart->id)->get(route('cart.show'));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('storefront/cart/show')
                ->has('pricesIncludeTax')
                ->has('displayTaxTotals')
        );
});

test('creates a cart for a visitor who does not have one yet', function () {
    get(route('cart.show'))->assertOk();

    expect(Cart::query()->count())->toBe(1);
});

test('the cart page does not resend a cart cookie that is already correct', function () {
    $cart = Cart::factory()->create();

    withUnencryptedCookie('cart_id', $cart->id)->get(route('cart.show'))
        ->assertOk()
        ->assertCookieMissing('cart_id');
});

test('the cart page sets the cart cookie for a visitor without one', function () {
    get(route('cart.show'))
        ->assertOk()
        ->assertCookie('cart_id', Cart::query()->sole()->id, false);
});

test('associates cart with authenticated user', function () {
    $user = User::factory()->create();
    $cart = Cart::factory()->create(['customer_id' => null]);

    actingAs($user);

    $response = withUnencryptedCookie('cart_id', $cart->id)->get(route('cart.show'));

    $response->assertOk();

    expect($cart->refresh()->customer_id)->toBe($user->id);
});

test('reuses existing user cart', function () {
    $user = User::factory()->create();
    $existingCart = Cart::factory()->create(['customer_id' => $user->id]);

    $response = actingAs($user)->get(route('cart.show'));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->where('cart.id', $existingCart->id)
        );
});

test('displays cart with multiple items', function () {
    $cart = Cart::factory()->create();
    $products = Product::factory()->count(3)->create();

    foreach ($products as $index => $product) {
        CartItem::factory()->for($cart)->create([
            'product_id' => $product->id,
            'quantity' => $index + 1,
            'unit_price' => '25.0000',
            'total_price' => (string) (25 * ($index + 1)) . '.00',
        ]);
    }

    $response = withUnencryptedCookie('cart_id', $cart->id)->get(route('cart.show'));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('storefront/cart/show')
                ->has('cart.items', 3)
        );
});

test('displays empty cart when no items', function () {
    $cart = Cart::factory()->create();

    $response = withUnencryptedCookie('cart_id', $cart->id)->get(route('cart.show'));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('storefront/cart/show')
                ->has('cart')
                ->where('cart.items', [])
        );
});

test('displays cart item with product details', function () {
    $cart = Cart::factory()->create();
    $product = Product::factory()->create([
        'title' => ['en' => 'Test Product'],
        'url_handle' => 'test-product',
    ]);

    CartItem::factory()->for($cart)->create([
        'product_id' => $product->id,
        'quantity' => 2,
        'unit_price' => '49.9900',
        'total_price' => '99.9800',
    ]);

    $response = withUnencryptedCookie('cart_id', $cart->id)->get(route('cart.show'));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('storefront/cart/show')
                ->has('cart.items.0.product')
                ->where('cart.items.0.quantity', 2)
                ->where('cart.items.0.unit_price', '49.9900')
                ->where('cart.items.0.total_price', '99.9800')
        );
});

test('displays cart totals correctly', function () {
    $cart = Cart::factory()->create([
        'subtotal' => '150.0000',
        'tax_total' => '15.0000',
        'shipping_total' => '10.0000',
        'discount_total' => '25.0000',
        'total' => '150.0000',
    ]);

    $response = withUnencryptedCookie('cart_id', $cart->id)->get(route('cart.show'));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->where('cart.subtotal', '150.0000')
                ->where('cart.tax_total', '15.0000')
                ->where('cart.shipping_total', '10.0000')
                ->where('cart.discount_total', '25.0000')
                ->where('cart.total', '150.0000')
        );
});

test('displays cart with coupon code', function () {
    $cart = Cart::factory()->create([
        'coupon_code' => 'SAVE20',
        'discount_total' => '20.0000',
    ]);

    $response = withUnencryptedCookie('cart_id', $cart->id)->get(route('cart.show'));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->where('cart.coupon_code', 'SAVE20')
                ->where('cart.discount_total', '20.0000')
        );
});

test('exposes the compare at price on discounted line items', function () {
    $cart = Cart::factory()->create();
    $product = Product::factory()->create();
    CartItem::factory()->for($cart)->create([
        'product_id' => $product->id,
        'quantity' => 1,
        'unit_price' => '25.0000',
        'compare_at_price' => '40.0000',
        'total_price' => '25.0000',
    ]);

    withUnencryptedCookie('cart_id', $cart->id)
        ->get(route('cart.show'))
        ->assertInertia(
            fn (AssertableInertia $page) => $page->where('cart.items.0.compare_at_price', '40.0000')
        );
});
