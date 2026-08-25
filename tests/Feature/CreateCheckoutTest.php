<?php

declare(strict_types=1);

use App\Actions\RecalculateCartTotalsAction;
use App\Actions\ResolveCartAction;
use App\Actions\SyncCartItemPricesAction;
use App\Http\Controllers\Storefront\CheckoutController;
use App\Http\Middleware\EnsureGuestCheckoutIsEnabled;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\CheckoutSession;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Queries\PendingCheckoutDraftByCartQuery;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\get;
use function Pest\Laravel\withUnencryptedCookie;

covers([
    CheckoutController::class,
    EnsureGuestCheckoutIsEnabled::class,
    ResolveCartAction::class,
    SyncCartItemPricesAction::class,
    RecalculateCartTotalsAction::class,
    PendingCheckoutDraftByCartQuery::class,
]);

uses()->group('checkout');

test('displays checkout page', function () {
    $cart = Cart::factory()->create([
        'subtotal' => '100.0000',
    ]);

    $product = Product::factory()->create();
    CartItem::factory()->for($cart)->create([
        'product_id' => $product->id,
        'quantity' => 1,
        'unit_price' => '100.0000',
        'total_price' => '100.0000',
    ]);

    $response = withUnencryptedCookie('cart_id', $cart->id)->get(route('checkout.create'));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('storefront/checkout/create')
                ->has('cart')
                ->has('savedAddresses')
                ->has('addressFieldRules.country_code')
                ->has('storeCountryCode')
                ->has('pricesIncludeTax')
                ->has('displayTaxTotals')
                ->where('recoveredCheckout', null)
        );
});

test('guests get an empty saved-address book', function () {
    $cart = Cart::factory()->create();
    CartItem::factory()->for($cart)->create(['quantity' => 1, 'unit_price' => '10.0000', 'total_price' => '10.0000']);

    withUnencryptedCookie('cart_id', $cart->id)->get(route('checkout.create'))
        ->assertInertia(fn (AssertableInertia $page) => $page->has('savedAddresses', 0));
});

test('syncs stale cart item prices on checkout page load', function () {
    $product = Product::factory()->create(['price' => '80.0000']);

    $cart = Cart::factory()->create(['subtotal' => '50.0000']);
    CartItem::factory()->for($cart)->create([
        'product_id' => $product->id,
        'quantity' => 1,
        'unit_price' => '50.0000',
        'total_price' => '50.0000',
    ]);

    withUnencryptedCookie('cart_id', $cart->id)->get(route('checkout.create'))
        ->assertOk();

    assertDatabaseHas('cart_items', [
        'cart_id' => $cart->id,
        'product_id' => $product->id,
        'unit_price' => '80.0000',
        'total_price' => '80.0000',
    ]);

    assertDatabaseHas('carts', [
        'id' => $cart->id,
        'subtotal' => '80.0000',
    ]);
});

test('the checkout page does not resend a cart cookie that is already correct', function () {
    $cart = Cart::factory()->create();

    withUnencryptedCookie('cart_id', $cart->id)->get(route('checkout.create'))
        ->assertOk()
        ->assertCookieMissing('cart_id');
});

test('the checkout page sets the cart cookie for a visitor without one', function () {
    get(route('checkout.create'))
        ->assertOk()
        ->assertCookie('cart_id', Cart::query()->sole()->id, false);
});

test('a visitor without a cart gets one created', function () {
    get(route('checkout.create'))->assertOk();

    expect(Cart::query()->count())->toBe(1);
});

test('the checkout draft is restored on the visitor own cart', function () {
    $cart = Cart::factory()->create();
    $product = Product::factory()->active()->create();
    CartItem::factory()->for($cart)->create([
        'product_id' => $product->id,
        'quantity' => 1,
        'unit_price' => '10.0000',
        'total_price' => '10.0000',
    ]);
    CheckoutSession::factory()->create([
        'cart_id' => $cart->id,
        'customer_email' => 'alice@example.com',
    ]);

    withUnencryptedCookie('cart_id', $cart->id)
        ->get(route('checkout.create'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('recoveredCheckout.customer_email', 'alice@example.com')
        );
});

test('guests are sent to sign in when guest checkout is disabled', function () {
    Setting::setValue('guest_checkout_enabled', false);

    $cart = Cart::factory()->create();

    withUnencryptedCookie('cart_id', $cart->id)
        ->get(route('checkout.create'))
        ->assertRedirect(route('account.login'));
});

test('customers can still check out when guest checkout is disabled', function () {
    Setting::setValue('guest_checkout_enabled', false);

    $cart = Cart::factory()->create();

    actingAs(User::factory()->create())
        ->withUnencryptedCookie('cart_id', $cart->id)
        ->get(route('checkout.create'))
        ->assertOk();
});

test('guests cannot post a checkout when guest checkout is disabled', function () {
    Setting::setValue('guest_checkout_enabled', false);

    $cart = Cart::factory()->create();

    withUnencryptedCookie('cart_id', $cart->id)
        ->post(route('checkout.store'), ['customer_email' => 'guest@example.com'])
        ->assertRedirect(route('account.login'));

    expect(CheckoutSession::query()->count())->toBe(0);
});

test('a json checkout submission is rejected with a message instead of a login redirect', function () {
    Setting::setValue('guest_checkout_enabled', false);

    $cart = Cart::factory()->create();

    withUnencryptedCookie('cart_id', $cart->id)
        ->postJson(route('checkout.store'), ['customer_email' => 'guest@example.com'])
        ->assertStatus(422)
        ->assertJson(['message' => __('Please sign in to complete your order.')]);
});
