<?php

declare(strict_types=1);

use App\Actions\ConfirmCheckoutPaymentAction;
use App\Enums\CheckoutSessionStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Storefront\CheckoutSuccessController;
use App\Http\Requests\Storefront\ShowCheckoutSuccessRequest;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\CheckoutSession;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentGateway;
use App\Models\Product;
use App\Models\Region;
use App\Models\ShippingCarrier;
use App\Models\ShippingRate;
use App\Payment\Contracts\PaymentDriver;
use App\Payment\DTOs\RefundResult;
use App\Payment\DTOs\SessionResult;
use App\Payment\DTOs\VerificationResult;
use App\Payment\DTOs\WebhookEvent;
use App\Payment\PaymentManager;
use App\Queries\CheckoutSuccessPageQuery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\get;
use function Pest\Laravel\withUnencryptedCookie;

covers(
    CheckoutSuccessController::class,
    ShowCheckoutSuccessRequest::class,
    ConfirmCheckoutPaymentAction::class,
    CheckoutSuccessPageQuery::class,
);

uses()->group('checkout');

function fakePaymentDriverWithVerification(VerificationResult $verification): PaymentDriver
{
    return new class($verification) implements PaymentDriver
    {
        public function __construct(private readonly VerificationResult $verification)
        {
        }

        public function createSession(App\Payment\DTOs\CreateSession $session): SessionResult
        {
            throw new RuntimeException('Not implemented');
        }

        public function verifyPayment(?string $gatewayReference, PaymentStatus $currentStatus): VerificationResult
        {
            return $this->verification;
        }

        public function refund(App\Payment\DTOs\RefundPayment $refund): RefundResult
        {
            throw new RuntimeException('Not implemented');
        }

        public function verifyWebhook(Request $request): bool
        {
            return false;
        }

        public function parseWebhook(Request $request): WebhookEvent
        {
            throw new RuntimeException('Not implemented');
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

function createPendingCheckoutSession(PaymentGateway $gateway): array
{
    $product = Product::factory()->available()->create([
        'price' => '50.0000',
        'track_stock' => false,
    ]);

    $cart = Cart::factory()->create(['subtotal' => '50.0000']);
    CartItem::factory()->for($cart)->create([
        'product_id' => $product->id,
        'quantity' => 1,
        'unit_price' => '50.0000',
        'total_price' => '50.0000',
    ]);

    $carrier = ShippingCarrier::factory()->active()->create();
    $region = Region::factory()->active()->create(['countries' => ['US']]);
    $shippingRate = ShippingRate::factory()->for($carrier, 'carrier')->for($region)->active()->create(['rate' => '5.0000']);

    $session = CheckoutSession::factory()->pending()->create([
        'cart_id' => $cart->id,
        'customer_email' => 'test@example.com',
        'shipping_address' => [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address_line_1' => '123 Main St',
            'city' => 'New York',
            'state' => 'NY',
            'postal_code' => '10001',
            'country_code' => 'US',
        ],
        'billing_address' => null,
        'different_billing_address' => false,
        'shipping_rate_id' => $shippingRate->id,
        'shipping_carrier_id' => $carrier->id,
        'shipping_carrier_name' => $carrier->getTranslations('name'),
        'shipping_rate_name' => $shippingRate->getTranslations('name'),
        'payment_gateway_id' => $gateway->id,
        'payment_gateway_name' => $gateway->getTranslations('name'),
        'items' => [
            [
                'product_id' => $product->id,
                'product_variant_id' => null,
                'quantity' => 1,
                'unit_price' => '50.0000',
                'total_price' => '50.0000',
                'product_title' => $product->getTranslations('title'),
                'product_sku' => $product->sku,
                'variant_title' => null,
                'variant_options' => [],
                'weight' => $product->weight,
                'weight_unit' => $product->weight_unit,
            ],
        ],
        'currency_code' => 'USD',
        'exchange_rate' => '1.0000',
        'prices_include_tax' => false,
        'subtotal' => '50.0000',
        'tax_total' => '0.0000',
        'shipping_total' => '5.0000',
        'discount_total' => '0.0000',
        'total' => '55.0000',
    ]);

    return ['session' => $session, 'product' => $product, 'cart' => $cart];
}

test('displays checkout success page', function () {
    $gateway = PaymentGateway::factory()->cod()->active()->create();
    $order = Order::factory()
        ->for($gateway, 'paymentGateway')
        ->inProgress()
        ->create();

    $session = CheckoutSession::factory()->completed()->create([
        'payment_gateway_id' => $gateway->id,
        'order_id' => $order->id,
    ]);

    $url = URL::temporarySignedRoute('checkout.success', now()->addDay(), ['session' => $session->id]);

    get($url)
        ->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('storefront/checkout/success')
                ->where('order.id', $order->id)
                ->has('order.trackUrl')
        );
});

test('passes the order analytics payload to the success page', function () {
    $gateway = PaymentGateway::factory()->cod()->active()->create();
    $order = Order::factory()
        ->for($gateway, 'paymentGateway')
        ->inProgress()
        ->create([
            'currency_code' => 'USD',
            'total' => '120.0000',
            'tax_total' => '10.0000',
            'shipping_total' => '5.0000',
        ]);
    OrderItem::factory()->for($order)->create([
        'product_title' => ['en' => 'Widget'],
        'unit_price' => '50.0000',
        'quantity' => 2,
    ]);

    $session = CheckoutSession::factory()->completed()->create([
        'payment_gateway_id' => $gateway->id,
        'order_id' => $order->id,
    ]);

    $url = URL::temporarySignedRoute('checkout.success', now()->addDay(), ['session' => $session->id]);

    get($url)
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->where('order.id', $order->id)
                ->where('order.currency_code', 'USD')
                ->where('order.total', '120.0000')
                ->where('order.tax_total', '10.0000')
                ->where('order.shipping_total', '5.0000')
                ->has('order.items', 1)
                ->where('order.items.0.product_title.en', 'Widget')
                ->where('order.items.0.unit_price', '50.0000')
                ->where('order.items.0.quantity', 2)
        );
});

test('clears the cart the order was placed from', function () {
    $gateway = PaymentGateway::factory()->cod()->active()->create();
    ['session' => $session, 'cart' => $cart] = createPendingCheckoutSession($gateway);

    expect($cart->refresh()->items)->toHaveCount(1);

    $url = URL::temporarySignedRoute('checkout.success', now()->addDay(), ['session' => $session->id]);

    withUnencryptedCookie('cart_id', $cart->id)->get($url)->assertOk();

    expect($cart->refresh()->items)->toHaveCount(0);
});

test('does not clear the cart of whoever happens to open the success page', function () {
    $gateway = PaymentGateway::factory()->cod()->active()->create();
    ['session' => $session] = createPendingCheckoutSession($gateway);

    $onlooker = Cart::factory()->create();
    $product = Product::factory()->available()->create();
    CartItem::factory()->for($onlooker)->create(['product_id' => $product->id]);

    $url = URL::temporarySignedRoute('checkout.success', now()->addDay(), ['session' => $session->id]);

    withUnencryptedCookie('cart_id', $onlooker->id)->get($url)->assertOk();

    expect($onlooker->refresh()->items)->toHaveCount(1);
});

test('verifies and updates unpaid payment on success page', function () {
    $gateway = PaymentGateway::factory()->cod()->active()->create();
    ['session' => $session] = createPendingCheckoutSession($gateway);

    PaymentManager::fake(fakePaymentDriverWithVerification(
        new VerificationResult(status: PaymentStatus::Paid, gatewayReference: 'pi_resolved_123'),
    ));

    $url = URL::temporarySignedRoute('checkout.success', now()->addDay(), ['session' => $session->id]);

    get($url)->assertOk();

    $session->refresh();
    expect($session->status)->toBe(CheckoutSessionStatus::Completed)
        ->and($session->order_id)->not->toBeNull();

    $order = Order::query()->findOrFail($session->order_id);
    expect($order->payment_status)->toBe(PaymentStatus::Paid);

    $transaction = App\Models\OrderTransaction::query()->where('order_id', $order->id)->first();
    expect($transaction)->not->toBeNull()
        ->and($transaction->gateway_reference)->toBe('pi_resolved_123');
});

test('does not update payment when verification returns same status', function () {
    $gateway = PaymentGateway::factory()->cod()->active()->create();
    ['session' => $session] = createPendingCheckoutSession($gateway);

    PaymentManager::fake(fakePaymentDriverWithVerification(
        new VerificationResult(status: PaymentStatus::Unpaid, gatewayReference: 'txn_test_123'),
    ));

    $url = URL::temporarySignedRoute('checkout.success', now()->addDay(), ['session' => $session->id]);

    get($url)->assertOk();

    $session->refresh();
    expect($session->status)->toBe(CheckoutSessionStatus::Completed)
        ->and($session->order_id)->not->toBeNull();

    $order = Order::query()->findOrFail($session->order_id);
    expect($order->payment_status)->toBe(PaymentStatus::Unpaid);
});

test('redirects to cancel page when payment verification returns failed', function () {
    $gateway = PaymentGateway::factory()->cod()->active()->create();
    ['session' => $session] = createPendingCheckoutSession($gateway);

    PaymentManager::fake(fakePaymentDriverWithVerification(
        new VerificationResult(status: PaymentStatus::Failed, gatewayReference: 'txn_test_failed'),
    ));

    $url = URL::temporarySignedRoute('checkout.success', now()->addDay(), ['session' => $session->id]);

    $response = get($url);

    $response->assertRedirect();
    expect($response->headers->get('Location'))->toContain('checkout/cancel');
});

test('redirects to cancel page when payment verification returns canceled', function () {
    $gateway = PaymentGateway::factory()->cod()->active()->create();
    ['session' => $session] = createPendingCheckoutSession($gateway);

    PaymentManager::fake(fakePaymentDriverWithVerification(
        new VerificationResult(status: PaymentStatus::Canceled, gatewayReference: 'txn_test_canceled'),
    ));

    $url = URL::temporarySignedRoute('checkout.success', now()->addDay(), ['session' => $session->id]);

    $response = get($url);

    $response->assertRedirect();
    expect($response->headers->get('Location'))->toContain('checkout/cancel');
});

test('rejects unsigned request to success page', function () {
    $session = CheckoutSession::factory()->pending()->create();

    get(route('checkout.success', ['session' => $session->id]))
        ->assertForbidden();
});

test('allows payment provider query params without breaking signature', function () {
    $gateway = PaymentGateway::factory()->cod()->active()->create();
    $order = Order::factory()
        ->for($gateway, 'paymentGateway')
        ->inProgress()
        ->create();

    $session = CheckoutSession::factory()->completed()->create([
        'payment_gateway_id' => $gateway->id,
        'order_id' => $order->id,
    ]);

    $url = URL::temporarySignedRoute('checkout.success', now()->addDay(), ['session' => $session->id]);
    $url .= '&payment_intent=pi_test_123&payment_intent_client_secret=pi_test_secret&redirect_status=succeeded';
    $url .= '&tap_id=chg_TS03A3320&data=7FA1AC8EB9B41026311F636CF6E28164';

    get($url)->assertOk();
});

test('redirects to cancel page for canceled session', function () {
    $gateway = PaymentGateway::factory()->cod()->active()->create();
    $session = CheckoutSession::factory()->canceled()->create([
        'payment_gateway_id' => $gateway->id,
    ]);

    $url = URL::temporarySignedRoute('checkout.success', now()->addDay(), ['session' => $session->id]);

    $response = get($url);

    $response->assertRedirect();
    expect($response->headers->get('Location'))->toContain('checkout/cancel');
});

test('redirects to cancel page when confirmation throws exception', function () {
    $gateway = PaymentGateway::factory()->cod()->active()->create();
    ['session' => $session, 'product' => $product] = createPendingCheckoutSession($gateway);

    PaymentManager::fake(fakePaymentDriverWithVerification(
        new VerificationResult(status: PaymentStatus::Paid, gatewayReference: 'pi_test_exception'),
    ));

    // Delete the product so confirmation throws when building order items
    $product->forceDelete();

    $url = URL::temporarySignedRoute('checkout.success', now()->addDay(), ['session' => $session->id]);

    $response = get($url);

    $response->assertRedirect();
    expect($response->headers->get('Location'))->toContain('checkout/cancel');
});

test('returns existing order when a completed payment session already points to an order', function () {
    $gateway = PaymentGateway::factory()->cod()->active()->create();
    ['session' => $session] = createPendingCheckoutSession($gateway);

    $order = Order::factory()->for($gateway, 'paymentGateway')->inProgress()->create();
    $session->update(['order_id' => $order->id]);

    App\Models\PaymentSession::query()->create([
        'payment_gateway_id' => $gateway->id,
        'owner_type' => CheckoutSession::class,
        'owner_id' => $session->id,
        'purpose' => App\Enums\PaymentSessionPurpose::Checkout,
        'currency_code' => 'USD',
        'amount' => '55.0000',
        'status' => App\Enums\PaymentSessionStatus::Completed,
        'completed_at' => now(),
        'return_url' => 'https://example.com',
    ]);

    PaymentManager::fake(fakePaymentDriverWithVerification(
        new VerificationResult(status: PaymentStatus::Paid, gatewayReference: 'pi_existing'),
    ));

    $url = URL::temporarySignedRoute('checkout.success', now()->addDay(), ['session' => $session->id]);

    get($url)->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('storefront/checkout/success'));

    expect($session->refresh()->order_id)->toBe($order->id)
        ->and(Order::query()->count())->toBe(1);
});

test('marks payment session completed after confirming a pending session', function () {
    $gateway = PaymentGateway::factory()->cod()->active()->create();
    ['session' => $session] = createPendingCheckoutSession($gateway);

    $paymentSession = App\Models\PaymentSession::query()->create([
        'payment_gateway_id' => $gateway->id,
        'owner_type' => CheckoutSession::class,
        'owner_id' => $session->id,
        'purpose' => App\Enums\PaymentSessionPurpose::Checkout,
        'currency_code' => 'USD',
        'amount' => '55.0000',
        'status' => App\Enums\PaymentSessionStatus::Pending,
        'gateway_reference' => 'pi_ref',
        'return_url' => 'https://example.com',
    ]);

    PaymentManager::fake(fakePaymentDriverWithVerification(
        new VerificationResult(status: PaymentStatus::Paid, gatewayReference: 'pi_ref'),
    ));

    $url = URL::temporarySignedRoute('checkout.success', now()->addDay(), ['session' => $session->id]);

    get($url)->assertOk();

    $paymentSession->refresh();
    expect($paymentSession->status)->toBe(App\Enums\PaymentSessionStatus::Completed)
        ->and($paymentSession->completed_at)->not->toBeNull();
});

test('handles session with no payment gateway as unpaid', function () {
    ['session' => $session] = createPendingCheckoutSession(PaymentGateway::factory()->cod()->active()->create());
    $session->update(['payment_gateway_id' => null]);

    $url = URL::temporarySignedRoute('checkout.success', now()->addDay(), ['session' => $session->id]);

    // The success page renders without blowing up; paymentGateway-less sessions are handled gracefully.
    get($url)->assertOk();
});

test('treats verification exception as unpaid and continues', function () {
    $gateway = PaymentGateway::factory()->cod()->active()->create();
    ['session' => $session] = createPendingCheckoutSession($gateway);

    $throwingDriver = new class() implements PaymentDriver
    {
        public function createSession(App\Payment\DTOs\CreateSession $session): SessionResult
        {
            throw new RuntimeException('no');
        }

        public function verifyPayment(?string $gatewayReference, PaymentStatus $currentStatus): VerificationResult
        {
            throw new RuntimeException('Gateway unreachable');
        }

        public function refund(App\Payment\DTOs\RefundPayment $refund): RefundResult
        {
            throw new RuntimeException('no');
        }

        public function verifyWebhook(Request $request): bool
        {
            return false;
        }

        public function parseWebhook(Request $request): WebhookEvent
        {
            throw new RuntimeException('no');
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

    PaymentManager::fake($throwingDriver);

    $url = URL::temporarySignedRoute('checkout.success', now()->addDay(), ['session' => $session->id]);

    // Exception in verification is caught and status defaults to Unpaid (not Failed/Canceled),
    // so confirmation proceeds and the page renders successfully.
    get($url)->assertOk();
});

test('skips verification when order is already paid', function () {
    $gateway = PaymentGateway::factory()->cod()->active()->create();
    $order = Order::factory()
        ->for($gateway, 'paymentGateway')
        ->inProgress()
        ->create();

    $session = CheckoutSession::factory()->completed()->create([
        'payment_gateway_id' => $gateway->id,
        'order_id' => $order->id,
    ]);

    $url = URL::temporarySignedRoute('checkout.success', now()->addDay(), ['session' => $session->id]);

    get($url)->assertOk();

    expect($order->refresh()->payment_status)->toBe(PaymentStatus::Paid);
});
