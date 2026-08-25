<?php

declare(strict_types=1);

use App\Actions\ResolveCartAction;
use App\Enums\CheckoutMode;
use App\Http\Controllers\Storefront\CheckoutPaymentOptionController;
use App\Http\Requests\Storefront\CheckoutPaymentOptionsRequest;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Currency;
use App\Models\PaymentGateway;
use App\Models\Product;
use App\Models\Region;
use App\Queries\EligiblePaymentOptionsQuery;

use function Pest\Laravel\withUnencryptedCookie;

covers([
    CheckoutPaymentOptionController::class,
    CheckoutPaymentOptionsRequest::class,
    ResolveCartAction::class,
    EligiblePaymentOptionsQuery::class,
]);

uses()->group('checkout');

function paymentCartWithItem(string $subtotal = '100.0000'): Cart
{
    $cart = Cart::factory()->create(['subtotal' => $subtotal]);

    $product = Product::factory()->create(['price' => $subtotal]);
    CartItem::factory()->for($cart)->create([
        'product_id' => $product->id,
        'quantity' => 1,
        'unit_price' => $subtotal,
        'total_price' => $subtotal,
    ]);

    return $cart;
}

test('returns payment options with correct json structure', function () {
    $cart = paymentCartWithItem();
    $gateway = PaymentGateway::factory()->cod()->active()->create();

    $response = withUnencryptedCookie('cart_id', $cart->id)->post(route('checkout.payment-options.index'), []);

    $response->assertOk()
        ->assertJsonStructure([
            'payment' => [
                '*' => ['id', 'name', 'driver'],
            ],
        ])
        ->assertJsonMissingPath('shipping')
        ->assertJsonCount(1, 'payment')
        ->assertJsonPath('payment.0.id', $gateway->id)
        ->assertJsonPath('payment.0.driver', 'cod');
});

test('filters payment options by address region', function () {
    $cart = paymentCartWithItem();

    $usRegion = Region::factory()->active()->create(['countries' => ['US']]);
    $caRegion = Region::factory()->active()->create(['countries' => ['CA']]);

    $usGateway = PaymentGateway::factory()->cod()->active()->create([
        'name' => 'US COD',
        'allowed_regions' => [$usRegion->id],
    ]);

    PaymentGateway::factory()->stripe()->active()->create([
        'name' => 'CA Stripe',
        'allowed_regions' => [$caRegion->id],
    ]);

    $response = withUnencryptedCookie('cart_id', $cart->id)->post(route('checkout.payment-options.index'), [
        'address' => [
            'country_code' => 'US',
            'state' => 'NY',
            'postal_code' => '10001',
        ],
    ]);

    $response->assertOk()
        ->assertJsonCount(1, 'payment')
        ->assertJsonPath('payment.0.id', $usGateway->id);
});

test('excludes region-restricted gateways when no address is provided', function () {
    $cart = paymentCartWithItem();

    $region = Region::factory()->active()->create(['countries' => ['US']]);
    $unrestricted = PaymentGateway::factory()->cod()->active()->create(['name' => 'Anywhere COD']);
    PaymentGateway::factory()->stripe()->active()->create([
        'name' => 'US Stripe',
        'allowed_regions' => [$region->id],
    ]);

    $response = withUnencryptedCookie('cart_id', $cart->id)->post(route('checkout.payment-options.index'), []);

    $response->assertOk()
        ->assertJsonCount(1, 'payment')
        ->assertJsonPath('payment.0.id', $unrestricted->id);
});

test('filters payment options by order value constraints', function () {
    $cart = paymentCartWithItem('25.0000');

    $eligibleGateway = PaymentGateway::factory()->cod()->active()->create([
        'name' => 'COD',
        'min_order_value' => '10.0000',
        'max_order_value' => '50.0000',
    ]);

    PaymentGateway::factory()->stripe()->active()->create([
        'name' => 'Stripe',
        'min_order_value' => '100.0000',
        'max_order_value' => '500.0000',
    ]);

    $response = withUnencryptedCookie('cart_id', $cart->id)->post(route('checkout.payment-options.index'), []);

    $response->assertOk()
        ->assertJsonCount(1, 'payment')
        ->assertJsonPath('payment.0.id', $eligibleGateway->id);
});

test('excludes inactive gateways', function () {
    $cart = paymentCartWithItem();

    PaymentGateway::factory()->cod()->inactive()->create();
    $activeGateway = PaymentGateway::factory()->stripe()->active()->create();

    $response = withUnencryptedCookie('cart_id', $cart->id)->post(route('checkout.payment-options.index'), []);

    $response->assertOk()
        ->assertJsonCount(1, 'payment')
        ->assertJsonPath('payment.0.id', $activeGateway->id);
});

test('excludes gateways with excluded products', function () {
    $cart = Cart::factory()->create(['subtotal' => '100.0000']);

    $product = Product::factory()->create();
    CartItem::factory()->for($cart)->create([
        'product_id' => $product->id,
        'quantity' => 1,
        'unit_price' => '100.0000',
        'total_price' => '100.0000',
    ]);

    PaymentGateway::factory()->cod()->active()->create([
        'name' => 'Excluded COD',
        'excluded_products' => [$product->id],
    ]);

    $eligibleGateway = PaymentGateway::factory()->stripe()->active()->create([
        'name' => 'Stripe',
    ]);

    $response = withUnencryptedCookie('cart_id', $cart->id)->post(route('checkout.payment-options.index'), []);

    $response->assertOk()
        ->assertJsonCount(1, 'payment')
        ->assertJsonPath('payment.0.id', $eligibleGateway->id);
});

test('returns embedded checkout fields for stripe embedded gateway', function () {
    $cart = paymentCartWithItem();

    PaymentGateway::factory()->stripe()->active()->create([
        'credentials' => [
            'publishable_key' => 'pk_test_embedded',
            'secret_key' => 'sk_test_embedded',
            'checkout_mode' => CheckoutMode::Embedded->value,
        ],
    ]);

    $response = withUnencryptedCookie('cart_id', $cart->id)->post(route('checkout.payment-options.index'), []);

    $response->assertOk()
        ->assertJsonCount(1, 'payment')
        ->assertJsonPath('payment.0.driver', 'stripe')
        ->assertJsonPath('payment.0.checkout_mode', CheckoutMode::Embedded->value)
        ->assertJsonPath('payment.0.publishable_key', 'pk_test_embedded');
});

test('does not return embedded fields for stripe hosted gateway', function () {
    $cart = paymentCartWithItem();

    PaymentGateway::factory()->stripe()->active()->create([
        'credentials' => [
            'publishable_key' => 'pk_test_hosted',
            'secret_key' => 'sk_test_hosted',
            'checkout_mode' => CheckoutMode::Hosted->value,
        ],
    ]);

    $response = withUnencryptedCookie('cart_id', $cart->id)->post(route('checkout.payment-options.index'), []);

    $response->assertOk()
        ->assertJsonCount(1, 'payment')
        ->assertJsonPath('payment.0.driver', 'stripe')
        ->assertJsonMissingPath('payment.0.checkout_mode')
        ->assertJsonMissingPath('payment.0.publishable_key');
});

test('includes gateways when supported currencies is empty', function () {
    Currency::factory()->active()->create(['code' => 'BDT']);

    $cart = paymentCartWithItem();

    PaymentGateway::factory()->cod()->active()->create(['supported_currencies' => []]);
    PaymentGateway::factory()->stripe()->active()->create(['supported_currencies' => []]);

    $response = withUnencryptedCookie('cart_id', $cart->id)
        ->post(route('checkout.payment-options.index', ['currency' => 'BDT']), []);

    $response->assertOk()
        ->assertJsonCount(2, 'payment');
});

test('excludes gateways when active currency is not in supported currencies', function () {
    Currency::factory()->active()->create(['code' => 'BDT']);

    $cart = paymentCartWithItem();

    $codGateway = PaymentGateway::factory()->cod()->active()->create([
        'supported_currencies' => ['BDT', 'USD'],
    ]);
    PaymentGateway::factory()->paypal()->active()->create([
        'supported_currencies' => ['USD', 'EUR'],
    ]);

    $response = withUnencryptedCookie('cart_id', $cart->id)
        ->post(route('checkout.payment-options.index', ['currency' => 'BDT']), []);

    $response->assertOk()
        ->assertJsonCount(1, 'payment')
        ->assertJsonPath('payment.0.id', $codGateway->id);
});

test('includes gateways when active currency is in supported currencies', function () {
    Currency::factory()->active()->create(['code' => 'EUR']);

    $cart = paymentCartWithItem();

    PaymentGateway::factory()->cod()->active()->create(['supported_currencies' => ['EUR', 'USD']]);
    PaymentGateway::factory()->stripe()->active()->create(['supported_currencies' => ['EUR', 'USD', 'GBP']]);

    $response = withUnencryptedCookie('cart_id', $cart->id)
        ->post(route('checkout.payment-options.index', ['currency' => 'EUR']), []);

    $response->assertOk()
        ->assertJsonCount(2, 'payment');
});

test('returns payment options for a cart addressed by route param', function () {
    $cart = paymentCartWithItem('75.0000');
    $gateway = PaymentGateway::factory()->cod()->active()->create();

    $response = withUnencryptedCookie('cart_id', $cart->id)->post(route('checkout.payment-options.index'));

    $response->assertOk()
        ->assertJsonStructure(['payment' => ['*' => ['id', 'name', 'driver']]])
        ->assertJsonPath('payment.0.id', $gateway->id);
});

test('validates address country_code must be 2 characters when provided', function () {
    $cart = Cart::factory()->create();

    $response = withUnencryptedCookie('cart_id', $cart->id)->post(route('checkout.payment-options.index'), [
        'address' => [
            'country_code' => 'USA',
        ],
    ], ['Accept' => 'application/json']);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['address.country_code']);
});
