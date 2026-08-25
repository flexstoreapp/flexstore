<?php

declare(strict_types=1);

use App\Actions\StoreCheckoutSessionAction;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderAddressType;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Storefront\CheckoutController;
use App\Http\Requests\Storefront\StoreCheckoutRequest;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\OrderItemDownload;
use App\Models\PaymentGateway;
use App\Models\Product;
use App\Models\ProductDownload;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\CustomerOrderConfirmedNotification;
use App\Payment\Drivers\MockDriver;
use App\Payment\PaymentManager;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\withUnencryptedCookie;

covers(CheckoutController::class, StoreCheckoutRequest::class, StoreCheckoutSessionAction::class);

uses()->group('checkout', 'downloads');

function digitalOnlyCheckoutData(PaymentGateway $gateway): array
{
    return [
        'customer_email' => 'buyer@example.com',
        'payment_gateway_id' => $gateway->id,
        'billing_address' => [
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'address_line_1' => '1 Analytical Way',
            'city' => 'London',
            'state' => 'NY',
            'postal_code' => '10001',
            'country_code' => 'US',
        ],
        'different_billing_address' => false,
    ];
}

test('a digital-only cart checks out without shipping and auto-fulfills', function () {
    Notification::fake();
    Setting::setValue('notification_customer_order_confirmed', true);
    PaymentManager::fake(new MockDriver(alwaysSucceed: true));

    $product = Product::factory()->digital()->active()->create(['price' => '40.0000']);
    ProductDownload::factory()->create(['product_id' => $product->id]);

    $cart = Cart::factory()->create(['subtotal' => '40.0000']);
    CartItem::factory()->for($cart)->create([
        'product_id' => $product->id,
        'quantity' => 1,
        'unit_price' => '40.0000',
        'total_price' => '40.0000',
    ]);

    $gateway = PaymentGateway::factory()->stripe()->active()->create();

    $response = withUnencryptedCookie('cart_id', $cart->id)
        ->post(route('checkout.store'), digitalOnlyCheckoutData($gateway));

    $response->assertRedirect()->assertSessionHasNoErrors();

    // Following the redirect to the success route confirms the order.
    get($response->headers->get('Location'))->assertOk();

    $order = Order::query()->latest('id')->firstOrFail();

    expect($order->payment_status)->toBe(PaymentStatus::Paid)
        ->and($order->shipping_total)->toBe('0.0000')
        ->and($order->fulfillment_status)->toBe(FulfillmentStatus::Fulfilled)
        ->and($order->shipping_rate_id)->toBeNull();

    expect($order->addresses()->where('type', OrderAddressType::Shipping)->exists())->toBeFalse()
        ->and($order->addresses()->where('type', OrderAddressType::Billing)->exists())->toBeTrue();

    expect(OrderItemDownload::query()->where('order_id', $order->id)->count())->toBe(1);

    Notification::assertSentOnDemand(CustomerOrderConfirmedNotification::class);
});

test('a digital-only cart rejects a COD gateway', function () {
    $product = Product::factory()->digital()->active()->create(['price' => '40.0000']);
    ProductDownload::factory()->create(['product_id' => $product->id]);

    $cart = Cart::factory()->create(['subtotal' => '40.0000']);
    CartItem::factory()->for($cart)->create([
        'product_id' => $product->id,
        'quantity' => 1,
        'unit_price' => '40.0000',
        'total_price' => '40.0000',
    ]);

    $gateway = PaymentGateway::factory()->cod()->active()->create();

    withUnencryptedCookie('cart_id', $cart->id)
        ->post(route('checkout.store'), digitalOnlyCheckoutData($gateway))
        ->assertSessionHasErrors('payment_gateway_id');
});

test('a digital product with no download files cannot be purchased', function () {
    $product = Product::factory()->digital()->active()->create(['price' => '40.0000']);
    // No ProductDownload attached.

    $cart = Cart::factory()->create(['subtotal' => '40.0000']);
    CartItem::factory()->for($cart)->create([
        'product_id' => $product->id,
        'quantity' => 1,
        'unit_price' => '40.0000',
        'total_price' => '40.0000',
    ]);

    $gateway = PaymentGateway::factory()->stripe()->active()->create();

    withUnencryptedCookie('cart_id', $cart->id)
        ->post(route('checkout.store'), digitalOnlyCheckoutData($gateway))
        ->assertSessionHasErrors('items.0');
});

test('an authenticated customer digital-only cart resolved by id checks out without shipping', function () {
    PaymentManager::fake(new MockDriver(alwaysSucceed: true));

    $user = User::factory()->customer()->create();
    $product = Product::factory()->digital()->active()->create(['price' => '40.0000']);
    ProductDownload::factory()->create(['product_id' => $product->id]);

    $cart = Cart::factory()->create(['subtotal' => '40.0000', 'customer_id' => $user->id]);
    CartItem::factory()->for($cart)->create([
        'product_id' => $product->id,
        'quantity' => 1,
        'unit_price' => '40.0000',
        'total_price' => '40.0000',
    ]);

    $gateway = PaymentGateway::factory()->stripe()->active()->create();

    // No cart cookie — the cart resolves by customer_id, and the request must
    // not demand shipping for this digital-only cart.
    actingAs($user)
        ->post(route('checkout.store'), digitalOnlyCheckoutData($gateway))
        ->assertValid(['shipping_rate_id', 'shipping_address'])
        ->assertRedirect();
});

test('a digital-only cart does not require a shipping address', function () {
    $product = Product::factory()->digital()->active()->create(['price' => '40.0000']);

    $cart = Cart::factory()->create(['subtotal' => '40.0000']);
    CartItem::factory()->for($cart)->create([
        'product_id' => $product->id,
        'quantity' => 1,
        'unit_price' => '40.0000',
        'total_price' => '40.0000',
    ]);

    $gateway = PaymentGateway::factory()->stripe()->active()->create();

    withUnencryptedCookie('cart_id', $cart->id)
        ->post(route('checkout.store'), [
            'customer_email' => 'buyer@example.com',
            'payment_gateway_id' => $gateway->id,
            'different_billing_address' => false,
        ])
        ->assertInvalid(['billing_address'])
        ->assertValid(['shipping_rate_id', 'shipping_address']);
});
