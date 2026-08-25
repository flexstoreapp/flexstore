<?php

declare(strict_types=1);

use App\Actions\StoreCheckoutSessionAction;
use App\DTOs\CheckoutInitiationResult;
use App\DTOs\CheckoutSessionTotals;
use App\DTOs\StoreCheckoutInput;
use App\Enums\CheckoutSessionStatus;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\CheckoutSession;
use App\Models\Currency;
use App\Models\PaymentGateway;
use App\Models\Product;
use App\Models\Region;
use App\Models\Setting;
use App\Models\ShippingCarrier;
use App\Models\ShippingRate;
use App\Models\StockReservation;
use App\Models\User;
use App\Payment\DTOs\SessionResult;
use App\Payment\PaymentManager;
use Illuminate\Validation\ValidationException;

covers([
    StoreCheckoutSessionAction::class,
    StoreCheckoutInput::class,
    CheckoutInitiationResult::class,
    CheckoutSessionTotals::class,
]);

uses()->group('actions', 'checkout');

function createStoreCheckoutScenario(array $overrides = []): array
{
    $product = $overrides['product'] ?? Product::factory()->available()->create(['price' => '50.0000']);

    $cart = Cart::factory()->create(['subtotal' => $overrides['subtotal'] ?? '100.0000']);
    CartItem::factory()->for($cart)->create([
        'product_id' => $product->id,
        'quantity' => $overrides['quantity'] ?? 2,
        'unit_price' => '50.0000',
        'total_price' => $overrides['subtotal'] ?? '100.0000',
    ]);

    $carrier = ShippingCarrier::factory()->active()->create();
    $region = Region::factory()->active()->create(['countries' => ['US']]);
    $shippingRate = $overrides['shipping_rate'] ?? ShippingRate::factory()
        ->for($carrier, 'carrier')
        ->for($region)
        ->active()
        ->create(['rate' => '10.0000']);

    $paymentGateway = $overrides['payment_gateway'] ?? PaymentGateway::factory()->cod()->active()->create();

    $data = [
        'customer_email' => 'customer@example.com',
        'shipping_rate_id' => $shippingRate->id,
        'payment_gateway_id' => $paymentGateway->id,
        'shipping_address' => [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address_line_1' => '123 Main St',
            'city' => 'New York',
            'state' => 'NY',
            'postal_code' => '10001',
            'country_code' => 'US',
        ],
        'different_billing_address' => false,
        ...($overrides['data'] ?? []),
    ];

    return [
        'cart' => $cart,
        'product' => $product,
        'shipping_rate' => $shippingRate,
        'payment_gateway' => $paymentGateway,
        'data' => $data,
    ];
}

test('creates checkout session with correct fields', function () {
    PaymentManager::fake();
    $scenario = createStoreCheckoutScenario();

    $result = app(StoreCheckoutSessionAction::class)->handle(
        StoreCheckoutInput::fromArray($scenario['data']),
        $scenario['cart']->id,
    );

    expect($result->checkoutSession)->toBeInstanceOf(CheckoutSession::class)
        ->and($result->paymentSession)->toBeInstanceOf(SessionResult::class);

    $session = $result->checkoutSession;
    expect($session->cart_id)->toBe($scenario['cart']->id)
        ->and($session->customer_email)->toBe('customer@example.com')
        ->and($session->status)->toBe(CheckoutSessionStatus::Pending)
        ->and($session->customer_id)->toBeNull()
        ->and($session->shipping_rate_id)->toBe($scenario['shipping_rate']->id)
        ->and($session->region_id)->toBe($scenario['shipping_rate']->region_id)
        ->and($session->payment_gateway_id)->toBe($scenario['payment_gateway']->id)
        ->and($session->subtotal)->toBe('100.0000')
        ->and($session->shipping_total)->toBe('10.0000')
        ->and($session->expires_at)->not->toBeNull();
});

test('links checkout session to authenticated customer', function () {
    PaymentManager::fake();
    $customer = User::factory()->customer()->create();
    $scenario = createStoreCheckoutScenario();

    $result = app(StoreCheckoutSessionAction::class)->handle(
        StoreCheckoutInput::fromArray($scenario['data']),
        $scenario['cart']->id,
        $customer,
    );

    expect($result->checkoutSession->customer_id)->toBe($customer->id);
});

test('guest checkout sets customer_id to null', function () {
    PaymentManager::fake();
    $scenario = createStoreCheckoutScenario();

    $result = app(StoreCheckoutSessionAction::class)->handle(
        StoreCheckoutInput::fromArray($scenario['data']),
        $scenario['cart']->id,
    );

    expect($result->checkoutSession->customer_id)->toBeNull();
});

test('throws when cart is empty', function () {
    PaymentManager::fake();
    $cart = Cart::factory()->create(['subtotal' => '0.0000']);

    $data = [
        'customer_email' => 'customer@example.com',
        'shipping_rate_id' => 1,
        'payment_gateway_id' => 1,
        'shipping_address' => [],
        'different_billing_address' => false,
    ];

    app(StoreCheckoutSessionAction::class)->handle(StoreCheckoutInput::fromArray($data), $cart->id);
})->throws(ValidationException::class);

test('throws when price increased since cart was viewed', function () {
    PaymentManager::fake();
    $product = Product::factory()->available()->create(['price' => '120.0000']);

    $scenario = createStoreCheckoutScenario([
        'product' => $product,
        'subtotal' => '100.0000',
    ]);

    app(StoreCheckoutSessionAction::class)->handle(
        StoreCheckoutInput::fromArray($scenario['data']),
        $scenario['cart']->id,
    );
})->throws(ValidationException::class);

test('allows checkout when price decreased', function () {
    PaymentManager::fake();
    $product = Product::factory()->available()->create(['price' => '40.0000']);

    $scenario = createStoreCheckoutScenario([
        'product' => $product,
        'subtotal' => '100.0000',
    ]);

    $result = app(StoreCheckoutSessionAction::class)->handle(
        StoreCheckoutInput::fromArray($scenario['data']),
        $scenario['cart']->id,
    );

    expect($result->checkoutSession)->toBeInstanceOf(CheckoutSession::class);
});

test('throws when payment gateway does not exist', function () {
    PaymentManager::fake();
    $scenario = createStoreCheckoutScenario();
    $scenario['data']['payment_gateway_id'] = 99999;

    app(StoreCheckoutSessionAction::class)->handle(
        StoreCheckoutInput::fromArray($scenario['data']),
        $scenario['cart']->id,
    );
})->throws(ValidationException::class);

test('throws when payment gateway is inactive', function () {
    PaymentManager::fake();
    $gateway = PaymentGateway::factory()->cod()->inactive()->create();
    $scenario = createStoreCheckoutScenario(['payment_gateway' => $gateway]);

    app(StoreCheckoutSessionAction::class)->handle(
        StoreCheckoutInput::fromArray($scenario['data']),
        $scenario['cart']->id,
    );
})->throws(ValidationException::class);

test('throws when shipping rate does not exist', function () {
    PaymentManager::fake();
    $scenario = createStoreCheckoutScenario();
    $scenario['data']['shipping_rate_id'] = 99999;

    app(StoreCheckoutSessionAction::class)->handle(
        StoreCheckoutInput::fromArray($scenario['data']),
        $scenario['cart']->id,
    );
})->throws(ValidationException::class);

test('throws when shipping rate is inactive', function () {
    PaymentManager::fake();
    $carrier = ShippingCarrier::factory()->active()->create();
    $region = Region::factory()->active()->create(['countries' => ['US']]);
    $rate = ShippingRate::factory()->for($carrier, 'carrier')->for($region)->inactive()->create();

    $scenario = createStoreCheckoutScenario(['shipping_rate' => $rate]);

    app(StoreCheckoutSessionAction::class)->handle(
        StoreCheckoutInput::fromArray($scenario['data']),
        $scenario['cart']->id,
    );
})->throws(ValidationException::class);

test('creates stock reservations for cart items', function () {
    PaymentManager::fake();
    $product = Product::factory()->available()->create([
        'price' => '50.0000',
        'track_stock' => true,
        'stock' => 10,
    ]);

    $scenario = createStoreCheckoutScenario(['product' => $product]);

    $result = app(StoreCheckoutSessionAction::class)->handle(
        StoreCheckoutInput::fromArray($scenario['data']),
        $scenario['cart']->id,
    );

    $reservations = StockReservation::query()
        ->where('checkout_session_id', $result->checkoutSession->id)
        ->get();

    expect($reservations)->toHaveCount(1)
        ->and($reservations->first()->product_id)->toBe($product->id)
        ->and($reservations->first()->quantity)->toBe(2);
});

test('reuses previous pending checkout session for the cart', function () {
    PaymentManager::fake();
    $scenario = createStoreCheckoutScenario();

    $previousSession = CheckoutSession::factory()->pending()->create([
        'cart_id' => $scenario['cart']->id,
    ]);

    $result = app(StoreCheckoutSessionAction::class)->handle(
        StoreCheckoutInput::fromArray($scenario['data']),
        $scenario['cart']->id,
    );

    expect($result->checkoutSession->id)->toBe($previousSession->id)
        ->and($previousSession->refresh()->status)->toBe(CheckoutSessionStatus::Pending);
});

test('stores different billing address when provided', function () {
    PaymentManager::fake();
    $scenario = createStoreCheckoutScenario([
        'data' => [
            'different_billing_address' => true,
            'billing_address' => [
                'first_name' => 'Jane',
                'last_name' => 'Doe',
                'address_line_1' => '456 Oak Ave',
                'city' => 'Brooklyn',
                'state' => 'NY',
                'postal_code' => '11201',
                'country_code' => 'US',
            ],
        ],
    ]);

    $result = app(StoreCheckoutSessionAction::class)->handle(
        StoreCheckoutInput::fromArray($scenario['data']),
        $scenario['cart']->id,
    );

    $session = $result->checkoutSession;
    expect($session->different_billing_address)->toBeTrue()
        ->and($session->billing_address)->toMatchArray([
            'first_name' => 'Jane',
            'address_line_1' => '456 Oak Ave',
            'city' => 'Brooklyn',
        ]);
});

test('uses shipping address as billing when different_billing_address is false', function () {
    PaymentManager::fake();
    $scenario = createStoreCheckoutScenario();

    $result = app(StoreCheckoutSessionAction::class)->handle(
        StoreCheckoutInput::fromArray($scenario['data']),
        $scenario['cart']->id,
    );

    $session = $result->checkoutSession;
    expect($session->different_billing_address)->toBeFalse()
        ->and($session->billing_address)->toMatchArray([
            'first_name' => 'John',
            'address_line_1' => '123 Main St',
        ]);
});

test('creates payment session with gateway reference', function () {
    PaymentManager::fake();
    $scenario = createStoreCheckoutScenario();

    $result = app(StoreCheckoutSessionAction::class)->handle(
        StoreCheckoutInput::fromArray($scenario['data']),
        $scenario['cart']->id,
    );

    $paymentSession = App\Models\PaymentSession::query()
        ->where('owner_type', CheckoutSession::class)
        ->where('owner_id', $result->checkoutSession->id)
        ->first();

    expect($paymentSession)->not->toBeNull()
        ->and($paymentSession->gateway_reference)->not->toBeNull();
});

test('stores notes when provided', function () {
    PaymentManager::fake();
    $scenario = createStoreCheckoutScenario([
        'data' => ['notes' => 'Please leave at front door'],
    ]);

    $result = app(StoreCheckoutSessionAction::class)->handle(
        StoreCheckoutInput::fromArray($scenario['data']),
        $scenario['cart']->id,
    );

    expect($result->checkoutSession->notes)->toBe('Please leave at front door');
});

test('throws when payment gateway is ineligible for order', function () {
    PaymentManager::fake();
    $product = Product::factory()->available()->create(['price' => '50.0000']);
    $gateway = PaymentGateway::factory()->cod()->active()->create([
        'excluded_products' => [$product->id],
    ]);

    $scenario = createStoreCheckoutScenario([
        'product' => $product,
        'payment_gateway' => $gateway,
    ]);

    app(StoreCheckoutSessionAction::class)->handle(
        StoreCheckoutInput::fromArray($scenario['data']),
        $scenario['cart']->id,
    );
})->throws(ValidationException::class);

test('scales amounts to currency decimal places without conversion', function () {
    PaymentManager::fake();

    Currency::factory()->create([
        'code' => 'JPY',
        'symbol' => '¥',
        'exchange_rate' => '1.0000',
        'decimal_places' => 0,
        'is_active' => true,
    ]);

    Setting::setValue('base_currency', 'JPY');

    $product = Product::factory()->available()->create(['price' => '3333.0000']);

    $scenario = createStoreCheckoutScenario([
        'product' => $product,
        'subtotal' => '6666.0000',
    ]);

    $result = app(StoreCheckoutSessionAction::class)->handle(
        StoreCheckoutInput::fromArray($scenario['data']),
        $scenario['cart']->id,
    );

    $session = $result->checkoutSession;
    expect($session->subtotal)->toBe('6666.0000')
        ->and($session->shipping_total)->toBe('10.0000')
        ->and($session->items[0]['unit_price'])->toBe('3333.0000')
        ->and($session->items[0]['total_price'])->toBe('6666.0000');
});
