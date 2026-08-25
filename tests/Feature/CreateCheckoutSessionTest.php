<?php

declare(strict_types=1);

use App\Actions\CancelCheckoutSessionAction;
use App\Actions\StoreCheckoutSessionAction;
use App\Http\Controllers\Storefront\CheckoutController;
use App\Http\Requests\Storefront\StoreCheckoutRequest;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\CheckoutSession;
use App\Models\PaymentGateway;
use App\Models\Product;
use App\Models\Region;
use App\Models\Setting;
use App\Models\ShippingCarrier;
use App\Models\ShippingRate;
use App\Models\User;
use App\Payment\Contracts\PaymentDriver;
use App\Payment\PaymentManager;
use App\Rules\SellingCountryRule;
use App\Utilities\CartItemValidator;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\post;
use function Pest\Laravel\withUnencryptedCookie;

covers([
    CheckoutController::class,
    StoreCheckoutRequest::class,
    StoreCheckoutSessionAction::class,
    CartItemValidator::class,
    CancelCheckoutSessionAction::class,
    SellingCountryRule::class,
]);

uses()->group('checkout');

test('creates checkout session from cart', function () {
    $cart = Cart::factory()->create([
        'subtotal' => '100.0000',
    ]);

    $product = Product::factory()->available()->create(['price' => '50.0000']);
    CartItem::factory()->for($cart)->create([
        'product_id' => $product->id,
        'quantity' => 2,
        'unit_price' => '50.0000',
        'total_price' => '100.0000',
    ]);

    $carrier = ShippingCarrier::factory()->active()->create();
    $region = Region::factory()->active()->create(['countries' => ['US']]);
    $shippingRate = ShippingRate::factory()->for($carrier, 'carrier')->for($region)->active()->create(['rate' => '10.0000']);
    $paymentGateway = PaymentGateway::factory()->cod()->active()->create();

    $checkoutData = [
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
    ];

    $response = withUnencryptedCookie('cart_id', $cart->id)->post(route('checkout.store'), $checkoutData);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    $redirectUrl = $response->headers->get('Location');
    expect($redirectUrl)->toContain('/checkout/success?')
        ->toContain('session=' . CheckoutSession::first()->id)
        ->toContain('expires=');

    assertDatabaseHas('checkout_sessions', [
        'customer_email' => 'customer@example.com',
    ]);

    $session = CheckoutSession::first();
    expect($session->cart_id)->toBe($cart->id);

    expect($cart->refresh()->items)->toHaveCount(1);
});

test('rejects a shipping country the store does not support', function () {
    Setting::setValue('selling_countries', ['BD']);

    $cart = Cart::factory()->create(['subtotal' => '100.0000']);
    $product = Product::factory()->available()->create();
    CartItem::factory()->for($cart)->create(['product_id' => $product->id]);

    $carrier = ShippingCarrier::factory()->active()->create();
    $region = Region::factory()->active()->create(['countries' => []]);
    $shippingRate = ShippingRate::factory()->for($carrier, 'carrier')->for($region)->active()->create();

    withUnencryptedCookie('cart_id', $cart->id)
        ->post(route('checkout.store'), [
            'customer_email' => 'customer@example.com',
            'shipping_rate_id' => $shippingRate->id,
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
        ])
        ->assertInvalid('shipping_address.country_code');
});

test('validates required fields', function () {
    $response = post(route('checkout.store'), []);

    $response->assertRedirectBack()
        ->assertInvalid([
            'customer_email',
            'shipping_rate_id',
            'shipping_address',
        ]);
});

test('validates customer email format', function () {
    $cart = Cart::factory()->create(['subtotal' => '100.0000']);
    $product = Product::factory()->available()->create();
    CartItem::factory()->for($cart)->create(['product_id' => $product->id]);

    $carrier = ShippingCarrier::factory()->active()->create();
    $region = Region::factory()->active()->create(['countries' => []]);
    $shippingRate = ShippingRate::factory()->for($carrier, 'carrier')->for($region)->active()->create();

    $response = withUnencryptedCookie('cart_id', $cart->id)->post(route('checkout.store'), [
        'customer_email' => 'invalid-email',
        'shipping_rate_id' => $shippingRate->id,
        'shipping_address' => [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address_line_1' => '123 Main St',
            'city' => 'New York',
            'state' => 'NY',
            'postal_code' => '10001',
            'country_code' => 'US',
        ],
    ]);

    $response->assertSessionHasErrors('customer_email');
});

test('validates billing address when different_billing_address is true', function () {
    $cart = Cart::factory()->create(['subtotal' => '100.0000']);
    $product = Product::factory()->available()->create();
    CartItem::factory()->for($cart)->create(['product_id' => $product->id]);

    $carrier = ShippingCarrier::factory()->active()->create();
    $region = Region::factory()->active()->create(['countries' => []]);
    $shippingRate = ShippingRate::factory()->for($carrier, 'carrier')->for($region)->active()->create();

    $response = withUnencryptedCookie('cart_id', $cart->id)->post(route('checkout.store'), [
        'customer_email' => 'customer@example.com',
        'shipping_rate_id' => $shippingRate->id,
        'shipping_address' => [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address_line_1' => '123 Main St',
            'city' => 'New York',
            'state' => 'NY',
            'postal_code' => '10001',
            'country_code' => 'US',
        ],
        'different_billing_address' => true,
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('billing_address');
});

test('creates checkout session with different billing address', function () {
    $checkoutCart = Cart::factory()->create(['subtotal' => '50.0000']);
    $product = Product::factory()->available()->create(['price' => '50.0000']);
    CartItem::factory()->for($checkoutCart)->create([
        'product_id' => $product->id,
        'quantity' => 1,
        'unit_price' => '50.0000',
        'total_price' => '50.0000',
    ]);

    $carrier = ShippingCarrier::factory()->active()->create();
    $region = Region::factory()->active()->create(['countries' => []]);
    $shippingRate = ShippingRate::factory()->for($carrier, 'carrier')->for($region)->active()->create();
    $paymentGateway = PaymentGateway::factory()->cod()->active()->create();

    $checkoutData = [
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
    ];

    $cartId = $checkoutCart->id;

    $response = withUnencryptedCookie('cart_id', $cartId)->post(route('checkout.store'), $checkoutData);

    $response->assertRedirect();

    $session = CheckoutSession::first();
    expect($session)->not->toBeNull()
        ->and($session->different_billing_address)->toBeTrue()
        ->and($session->shipping_address)->toMatchArray([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address_line_1' => '123 Main St',
            'city' => 'New York',
            'state' => 'NY',
            'postal_code' => '10001',
            'country_code' => 'US',
        ])
        ->and($session->billing_address)->toMatchArray([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'address_line_1' => '456 Oak Ave',
            'city' => 'Brooklyn',
            'state' => 'NY',
            'postal_code' => '11201',
            'country_code' => 'US',
        ]);
});

test('links checkout session to authenticated customer', function () {
    $customer = User::factory()->customer()->create();
    $cart = Cart::factory()->create(['subtotal' => '100.0000']);
    $product = Product::factory()->available()->create(['price' => '100.0000']);
    CartItem::factory()->for($cart)->create([
        'product_id' => $product->id,
        'quantity' => 1,
        'unit_price' => '100.0000',
        'total_price' => '100.0000',
    ]);

    $carrier = ShippingCarrier::factory()->active()->create();
    $region = Region::factory()->active()->create(['countries' => ['US']]);
    $shippingRate = ShippingRate::factory()->for($carrier, 'carrier')->for($region)->active()->create(['rate' => '5.0000']);
    $paymentGateway = PaymentGateway::factory()->cod()->active()->create();

    $response = actingAs($customer)
        ->withUnencryptedCookie('cart_id', $cart->id)
        ->post(route('checkout.store'), [
            'customer_email' => $customer->email,
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
        ]);

    $response->assertRedirect();

    $session = CheckoutSession::first();
    expect($session->customer_id)->toBe($customer->id);
});

test('does not link checkout session to customer for guest checkout', function () {
    $cart = Cart::factory()->create(['subtotal' => '100.0000']);
    $product = Product::factory()->available()->create(['price' => '100.0000']);
    CartItem::factory()->for($cart)->create([
        'product_id' => $product->id,
        'quantity' => 1,
        'unit_price' => '100.0000',
        'total_price' => '100.0000',
    ]);

    $carrier = ShippingCarrier::factory()->active()->create();
    $region = Region::factory()->active()->create(['countries' => ['US']]);
    $shippingRate = ShippingRate::factory()->for($carrier, 'carrier')->for($region)->active()->create(['rate' => '5.0000']);
    $paymentGateway = PaymentGateway::factory()->cod()->active()->create();

    $response = withUnencryptedCookie('cart_id', $cart->id)
        ->post(route('checkout.store'), [
            'customer_email' => 'guest@example.com',
            'shipping_rate_id' => $shippingRate->id,
            'payment_gateway_id' => $paymentGateway->id,
            'shipping_address' => [
                'first_name' => 'Guest',
                'last_name' => 'User',
                'address_line_1' => '456 Oak Ave',
                'city' => 'Boston',
                'state' => 'MA',
                'postal_code' => '02101',
                'country_code' => 'US',
            ],
            'different_billing_address' => false,
        ]);

    $response->assertRedirect();

    $session = CheckoutSession::first();
    expect($session->customer_id)->toBeNull();
});

test('rejects ineligible payment gateway at checkout', function () {
    $cart = Cart::factory()->create(['subtotal' => '100.0000']);
    $product = Product::factory()->available()->create(['price' => '100.0000']);
    CartItem::factory()->for($cart)->create([
        'product_id' => $product->id,
        'quantity' => 1,
        'unit_price' => '100.0000',
        'total_price' => '100.0000',
    ]);

    $carrier = ShippingCarrier::factory()->active()->create();
    $region = Region::factory()->active()->create(['countries' => ['US']]);
    $shippingRate = ShippingRate::factory()->for($carrier, 'carrier')->for($region)->active()->create(['rate' => '5.0000']);
    $paymentGateway = PaymentGateway::factory()->cod()->active()->create([
        'excluded_products' => [$product->id],
    ]);

    $response = withUnencryptedCookie('cart_id', $cart->id)
        ->post(route('checkout.store'), [
            'customer_email' => 'guest@example.com',
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
        ]);

    $response->assertRedirectBack()
        ->assertInvalid('payment_gateway_id');
});

test('rejects checkout when flash sale expiry causes price increase', function () {
    $product = Product::factory()->available()->create(['price' => '100.0000']);

    $cart = Cart::factory()->create(['subtotal' => '80.0000']);
    CartItem::factory()->for($cart)->create([
        'product_id' => $product->id,
        'quantity' => 1,
        'unit_price' => '80.0000',
        'total_price' => '80.0000',
    ]);

    $carrier = ShippingCarrier::factory()->active()->create();
    $region = Region::factory()->active()->create(['countries' => []]);
    $shippingRate = ShippingRate::factory()->for($carrier, 'carrier')->for($region)->active()->create();
    $paymentGateway = PaymentGateway::factory()->cod()->active()->create();

    $response = withUnencryptedCookie('cart_id', $cart->id)
        ->post(route('checkout.store'), [
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
        ]);

    $response->assertRedirectBack()
        ->assertInvalid('cart');

    expect(CheckoutSession::count())->toBe(0);
});

test('allows checkout when price decreased since cart was viewed', function () {
    $product = Product::factory()->available()->create(['price' => '80.0000']);

    $cart = Cart::factory()->create(['subtotal' => '100.0000']);
    CartItem::factory()->for($cart)->create([
        'product_id' => $product->id,
        'quantity' => 1,
        'unit_price' => '100.0000',
        'total_price' => '100.0000',
    ]);

    $carrier = ShippingCarrier::factory()->active()->create();
    $region = Region::factory()->active()->create(['countries' => []]);
    $shippingRate = ShippingRate::factory()->for($carrier, 'carrier')->for($region)->active()->create();
    $paymentGateway = PaymentGateway::factory()->cod()->active()->create();

    $response = withUnencryptedCookie('cart_id', $cart->id)
        ->post(route('checkout.store'), [
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
        ]);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(CheckoutSession::count())->toBe(1);
});

function fakePaymentDriverForCheckout(callable $createSession): PaymentDriver
{
    return new class($createSession) implements PaymentDriver
    {
        public function __construct(private $createSessionCallback)
        {
        }

        public function createSession(App\Payment\DTOs\CreateSession $session): App\Payment\DTOs\SessionResult
        {
            return ($this->createSessionCallback)($session);
        }

        public function verifyPayment(?string $gatewayReference, App\Enums\PaymentStatus $currentStatus): App\Payment\DTOs\VerificationResult
        {
            return new App\Payment\DTOs\VerificationResult(status: $currentStatus);
        }

        public function refund(App\Payment\DTOs\RefundPayment $refund): App\Payment\DTOs\RefundResult
        {
            return new App\Payment\DTOs\RefundResult(status: App\Enums\RefundStatus::Completed, amount: $refund->amount);
        }

        public function verifyWebhook(Illuminate\Http\Request $request): bool
        {
            return true;
        }

        public function parseWebhook(Illuminate\Http\Request $request): App\Payment\DTOs\WebhookEvent
        {
            return new App\Payment\DTOs\WebhookEvent(type: 'noop');
        }

        public function testConnection(): bool
        {
            return true;
        }

        public function supportsRefunds(): bool
        {
            return false;
        }

        public function isManual(): bool
        {
            return false;
        }
    };
}

function buildCheckoutPostData(ShippingRate $shippingRate, PaymentGateway $paymentGateway): array
{
    return [
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
    ];
}

test('an ordinary checkout still sends the billing address to the gateway', function () {
    $captured = null;

    PaymentManager::fake(fakePaymentDriverForCheckout(function ($session) use (&$captured) {
        $captured = $session;

        return new App\Payment\DTOs\SessionResult(
            status: App\Enums\PaymentStatus::Unpaid,
            redirectUrl: 'https://example.test/pay',
        );
    }));

    $cart = Cart::factory()->create(['subtotal' => '50.0000']);
    $product = Product::factory()->available()->create(['price' => '50.0000']);
    CartItem::factory()->for($cart)->create([
        'product_id' => $product->id,
        'quantity' => 1,
        'unit_price' => '50.0000',
        'total_price' => '50.0000',
    ]);

    $carrier = ShippingCarrier::factory()->active()->create();
    $region = Region::factory()->active()->create(['countries' => ['US']]);
    $shippingRate = ShippingRate::factory()->for($carrier, 'carrier')->for($region)->active()->create();
    $paymentGateway = PaymentGateway::factory()->stripe()->active()->create();

    withUnencryptedCookie('cart_id', $cart->id)
        ->post(route('checkout.store'), buildCheckoutPostData($shippingRate, $paymentGateway))
        ->assertSessionHasNoErrors();

    expect($captured->billingAddress)->not->toBeNull();
});

test('returns JSON 422 with failure reason when JSON request encounters payment failure', function () {
    PaymentManager::fake(fakePaymentDriverForCheckout(fn ($session) => new App\Payment\DTOs\SessionResult(
        status: App\Enums\PaymentStatus::Failed,
        redirectUrl: '',
        failureReason: 'Card declined',
    )));

    $cart = Cart::factory()->create(['subtotal' => '50.0000']);
    $product = Product::factory()->available()->create(['price' => '50.0000']);
    CartItem::factory()->for($cart)->create([
        'product_id' => $product->id,
        'quantity' => 1,
        'unit_price' => '50.0000',
        'total_price' => '50.0000',
    ]);

    $carrier = ShippingCarrier::factory()->active()->create();
    $region = Region::factory()->active()->create(['countries' => []]);
    $shippingRate = ShippingRate::factory()->for($carrier, 'carrier')->for($region)->active()->create();
    $paymentGateway = PaymentGateway::factory()->stripe()->active()->create();

    $response = withUnencryptedCookie('cart_id', $cart->id)
        ->withCredentials()
        ->postJson(route('checkout.store'), buildCheckoutPostData($shippingRate, $paymentGateway));

    $response->assertStatus(422)
        ->assertJson(['message' => 'Card declined']);

    expect(CheckoutSession::query()->where('status', App\Enums\CheckoutSessionStatus::Canceled)->count())->toBe(1);
});

test('throws validation error when HTML request encounters payment failure', function () {
    PaymentManager::fake(fakePaymentDriverForCheckout(fn ($session) => new App\Payment\DTOs\SessionResult(
        status: App\Enums\PaymentStatus::Failed,
        redirectUrl: '',
        failureReason: null,
    )));

    $cart = Cart::factory()->create(['subtotal' => '50.0000']);
    $product = Product::factory()->available()->create(['price' => '50.0000']);
    CartItem::factory()->for($cart)->create([
        'product_id' => $product->id,
        'quantity' => 1,
        'unit_price' => '50.0000',
        'total_price' => '50.0000',
    ]);

    $carrier = ShippingCarrier::factory()->active()->create();
    $region = Region::factory()->active()->create(['countries' => []]);
    $shippingRate = ShippingRate::factory()->for($carrier, 'carrier')->for($region)->active()->create();
    $paymentGateway = PaymentGateway::factory()->stripe()->active()->create();

    $response = withUnencryptedCookie('cart_id', $cart->id)
        ->post(route('checkout.store'), buildCheckoutPostData($shippingRate, $paymentGateway));

    $response->assertSessionHasErrors('payment');
});

test('returns JSON payment payload when JSON request receives a payload with return_url', function () {
    PaymentManager::fake(fakePaymentDriverForCheckout(fn ($session) => new App\Payment\DTOs\SessionResult(
        status: App\Enums\PaymentStatus::Unpaid,
        redirectUrl: $session->redirectUrls->failureUrl ?? '',
        gatewayReference: 'pi_test',
        payload: [
            'payment_intent_id' => 'pi_test',
            'return_url' => 'https://example.com/return',
            'client_secret' => 'pi_test_secret',
        ],
    )));

    $cart = Cart::factory()->create(['subtotal' => '50.0000']);
    $product = Product::factory()->available()->create(['price' => '50.0000']);
    CartItem::factory()->for($cart)->create([
        'product_id' => $product->id,
        'quantity' => 1,
        'unit_price' => '50.0000',
        'total_price' => '50.0000',
    ]);

    $carrier = ShippingCarrier::factory()->active()->create();
    $region = Region::factory()->active()->create(['countries' => []]);
    $shippingRate = ShippingRate::factory()->for($carrier, 'carrier')->for($region)->active()->create();
    $paymentGateway = PaymentGateway::factory()->stripe()->active()->create();

    $response = withUnencryptedCookie('cart_id', $cart->id)
        ->withCredentials()
        ->postJson(route('checkout.store'), buildCheckoutPostData($shippingRate, $paymentGateway));

    $response->assertOk()
        ->assertJsonPath('client_secret', 'pi_test_secret')
        ->assertJsonPath('payment_intent_id', 'pi_test')
        ->assertJsonPath('return_url', 'https://example.com/return');

    expect($response->json('cancel_url'))->toContain('/cancel');
});

test('redirects to external URL via Inertia::location when redirect host differs from app host', function () {
    PaymentManager::fake(fakePaymentDriverForCheckout(fn ($session) => new App\Payment\DTOs\SessionResult(
        status: App\Enums\PaymentStatus::Unpaid,
        redirectUrl: 'https://checkout.stripe.com/pay/cs_test_123',
    )));

    $cart = Cart::factory()->create(['subtotal' => '50.0000']);
    $product = Product::factory()->available()->create(['price' => '50.0000']);
    CartItem::factory()->for($cart)->create([
        'product_id' => $product->id,
        'quantity' => 1,
        'unit_price' => '50.0000',
        'total_price' => '50.0000',
    ]);

    $carrier = ShippingCarrier::factory()->active()->create();
    $region = Region::factory()->active()->create(['countries' => []]);
    $shippingRate = ShippingRate::factory()->for($carrier, 'carrier')->for($region)->active()->create();
    $paymentGateway = PaymentGateway::factory()->stripe()->active()->create();

    $response = withUnencryptedCookie('cart_id', $cart->id)
        ->withHeader('X-Inertia', 'true')
        ->post(route('checkout.store'), buildCheckoutPostData($shippingRate, $paymentGateway));

    $response->assertStatus(409)
        ->assertHeader('X-Inertia-Location', 'https://checkout.stripe.com/pay/cs_test_123');
});

test('completes checkout when request ids are strings', function () {
    $product = Product::factory()->available()->create(['price' => '50.0000']);

    $cart = Cart::factory()->create(['subtotal' => '50.0000']);
    CartItem::factory()->for($cart)->create([
        'product_id' => $product->id,
        'quantity' => 1,
        'unit_price' => '50.0000',
        'total_price' => '50.0000',
    ]);

    $carrier = ShippingCarrier::factory()->active()->create();
    $region = Region::factory()->active()->create(['countries' => []]);
    $shippingRate = ShippingRate::factory()->for($carrier, 'carrier')->for($region)->active()->create();
    $paymentGateway = PaymentGateway::factory()->cod()->active()->create();

    $response = withUnencryptedCookie('cart_id', $cart->id)
        ->post(route('checkout.store'), [
            'customer_email' => 'customer@example.com',
            'shipping_rate_id' => (string) $shippingRate->id,
            'payment_gateway_id' => (string) $paymentGateway->id,
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
        ]);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(CheckoutSession::count())->toBe(1);
});
