<?php

declare(strict_types=1);

use App\Actions\InitiateCheckoutSessionAction;
use App\Actions\RevalidateCartCouponAction;
use App\Actions\UpdatePendingCheckoutSessionAction;
use App\Enums\CheckoutSessionStatus;
use App\Enums\CheckoutStep;
use App\Http\Controllers\Storefront\CheckoutDraftController;
use App\Http\Requests\Storefront\UpdateCheckoutDraftRequest;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\CheckoutSession;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\withUnencryptedCookie;

covers(
    CheckoutDraftController::class,
    UpdateCheckoutDraftRequest::class,
    UpdatePendingCheckoutSessionAction::class,
    InitiateCheckoutSessionAction::class,
    RevalidateCartCouponAction::class,
);

uses()->group('checkout');

function draftCartWithCoupon(Coupon $coupon): Cart
{
    $cart = Cart::factory()->create(['subtotal' => '100.0000']);
    $product = Product::factory()->create(['price' => '100.0000']);
    CartItem::factory()->for($cart)->create([
        'product_id' => $product->id,
        'quantity' => 1,
        'unit_price' => '100.0000',
        'total_price' => '100.0000',
    ]);
    $cart->update(['coupon_code' => $coupon->code, 'discount_total' => '10.0000']);

    return $cart;
}

test('a signed-in visitor writes to the checkout draft of their own cart', function () {
    $customer = User::factory()->customer()->create();
    $cart = Cart::factory()->create(['customer_id' => $customer->id]);
    $session = CheckoutSession::factory()->initiated()->create(['cart_id' => $cart->id]);

    actingAs($customer)
        ->patch(route('checkout.draft.update'), [
            'notes' => 'Leave at door',
        ])->assertOk();

    expect($session->fresh()->notes)->toBe('Leave at door');
});

test('persists shipping address into pending session', function () {
    $cart = Cart::factory()->create();
    $session = CheckoutSession::factory()->initiated()->create(['cart_id' => $cart->id]);

    withUnencryptedCookie('cart_id', $cart->id)->patch(route('checkout.draft.update'), [
        'shipping_address' => [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'address_line_1' => '1 High St',
            'city' => 'London',
            'country_code' => 'GB',
        ],
    ])->assertOk();

    $session->refresh();
    expect($session->shipping_address)->not->toBeNull()
        ->and($session->shipping_address['city'])->toBe('London');
});

test('persists notes', function () {
    $cart = Cart::factory()->create();
    $session = CheckoutSession::factory()->initiated()->create(['cart_id' => $cart->id]);

    withUnencryptedCookie('cart_id', $cart->id)->patch(route('checkout.draft.update'), [
        'notes' => 'Leave at door',
    ])->assertOk();

    expect($session->fresh()->notes)->toBe('Leave at door');
});

test('persists the selected saved address id', function () {
    $user = User::factory()->create();
    $cart = Cart::factory()->create();
    $session = CheckoutSession::factory()->initiated()->create(['cart_id' => $cart->id]);
    $address = App\Models\CustomerAddress::factory()->for($user)->create();

    actingAs($user)
        ->withUnencryptedCookie('cart_id', $cart->id)
        ->patch(route('checkout.draft.update'), [
            'customer_address_id' => $address->id,
        ])->assertOk();

    expect($session->fresh()->customer_address_id)->toBe($address->id);
});

test('rejects a saved address id belonging to another user', function () {
    $user = User::factory()->create();
    $cart = Cart::factory()->create();
    CheckoutSession::factory()->initiated()->create(['cart_id' => $cart->id]);
    $otherAddress = App\Models\CustomerAddress::factory()->create();

    actingAs($user)
        ->withUnencryptedCookie('cart_id', $cart->id)
        ->patch(route('checkout.draft.update'), [
            'customer_address_id' => $otherAddress->id,
        ])->assertSessionHasErrors('customer_address_id');
});

test('persists billing address only when different_billing_address is true', function () {
    $cart = Cart::factory()->create();
    $session = CheckoutSession::factory()->initiated()->create(['cart_id' => $cart->id]);

    withUnencryptedCookie('cart_id', $cart->id)->patch(route('checkout.draft.update'), [
        'different_billing_address' => true,
        'billing_address' => [
            'first_name' => 'John',
            'last_name' => 'Smith',
            'address_line_1' => '2 Low St',
            'city' => 'Bristol',
            'country_code' => 'GB',
        ],
    ])->assertOk();

    $session->refresh();
    expect($session->different_billing_address)->toBeTrue()
        ->and($session->billing_address['city'])->toBe('Bristol');
});

test('does not bootstrap a session when there is no pending session and no email', function () {
    $cart = Cart::factory()->create();

    withUnencryptedCookie('cart_id', $cart->id)->patch(route('checkout.draft.update'), [
        'notes' => 'orphan',
    ])->assertOk();

    expect(CheckoutSession::where('cart_id', $cart->id)->count())->toBe(0);
});

test('creates a session when email is provided and none exists', function () {
    $cart = Cart::factory()->create();

    withUnencryptedCookie('cart_id', $cart->id)
        ->patch(route('checkout.draft.update'), [
            'customer_email' => 'guest@example.com',
        ])
        ->assertOk();

    assertDatabaseHas('checkout_sessions', [
        'cart_id' => $cart->id,
        'customer_email' => 'guest@example.com',
        'status' => CheckoutSessionStatus::Pending->value,
        'step' => CheckoutStep::ContactInformation->value,
    ]);
});

test('updates email on existing pending session', function () {
    $cart = Cart::factory()->create();
    $existing = CheckoutSession::factory()->initiated()->create([
        'cart_id' => $cart->id,
        'customer_email' => 'old@example.com',
    ]);

    withUnencryptedCookie('cart_id', $cart->id)
        ->patch(route('checkout.draft.update'), [
            'customer_email' => 'new@example.com',
        ])
        ->assertOk();

    expect($existing->refresh()->customer_email)->toBe('new@example.com')
        ->and(CheckoutSession::where('cart_id', $cart->id)->count())->toBe(1);
});

test('rejects malformed email', function () {
    $cart = Cart::factory()->create();

    withUnencryptedCookie('cart_id', $cart->id)
        ->withCredentials()
        ->patchJson(route('checkout.draft.update'), [
            'customer_email' => 'not-an-email',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('customer_email');
});

test('links customer_id when authenticated user is creating the session', function () {
    $customer = User::factory()->customer()->create();
    $cart = Cart::factory()->create();

    actingAs($customer)
        ->withUnencryptedCookie('cart_id', $cart->id)
        ->patch(route('checkout.draft.update'), [
            'customer_email' => $customer->email,
            'notes' => 'logged-in checkout',
        ])
        ->assertOk();

    $session = CheckoutSession::where('cart_id', $cart->id)->first();
    expect($session->customer_id)->toBe($customer->id)
        ->and($session->notes)->toBe('logged-in checkout');
});

test('drops the cart coupon when the entered email has exhausted its per-customer limit', function () {
    $coupon = Coupon::factory()->valid()->fixed(10)->create(['usage_limit_per_customer' => 1]);
    $cart = draftCartWithCoupon($coupon);

    Order::factory()->create([
        'coupon_id' => $coupon->id,
        'customer_email' => 'guest@example.com',
    ]);

    withUnencryptedCookie('cart_id', $cart->id)
        ->withCredentials()
        ->patchJson(route('checkout.draft.update'), [
            'customer_email' => 'guest@example.com',
        ])
        ->assertOk()
        ->assertJson(['coupon_removed' => true]);

    assertDatabaseHas('carts', [
        'id' => $cart->id,
        'coupon_code' => null,
        'discount_total' => '0.0000',
    ]);
});

test('keeps the cart coupon when it still validates for the entered email', function () {
    $coupon = Coupon::factory()->valid()->fixed(10)->create(['usage_limit_per_customer' => 1]);
    $cart = draftCartWithCoupon($coupon);

    withUnencryptedCookie('cart_id', $cart->id)
        ->withCredentials()
        ->patchJson(route('checkout.draft.update'), [
            'customer_email' => 'guest@example.com',
        ])
        ->assertOk()
        ->assertJson(['coupon_removed' => false]);

    assertDatabaseHas('carts', [
        'id' => $cart->id,
        'coupon_code' => $coupon->code,
    ]);
});
