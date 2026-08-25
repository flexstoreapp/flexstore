<?php

declare(strict_types=1);

use App\Actions\ResolveCartAction;
use App\Enums\ShippingRateType;
use App\Enums\TaxCategory;
use App\Http\Controllers\Storefront\CheckoutShippingOptionController;
use App\Http\Requests\Storefront\CheckoutShippingOptionsRequest;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\Region;
use App\Models\Setting;
use App\Models\ShippingCarrier;
use App\Models\ShippingRate;
use App\Models\TaxRate;
use App\Queries\EligibleShippingOptionsQuery;
use App\Utilities\OrderTaxCalculator;

use function Pest\Laravel\withUnencryptedCookie;

covers([
    CheckoutShippingOptionController::class,
    CheckoutShippingOptionsRequest::class,
    ResolveCartAction::class,
    EligibleShippingOptionsQuery::class,
    OrderTaxCalculator::class,
]);

uses()->group('checkout');

test('returns shipping options with correct json structure', function () {
    $cart = Cart::factory()->create(['subtotal' => '100.0000']);

    $product = Product::factory()->create();
    CartItem::factory()->for($cart)->create([
        'product_id' => $product->id,
        'quantity' => 1,
        'unit_price' => '100.0000',
        'total_price' => '100.0000',
    ]);

    $carrier = ShippingCarrier::factory()->active()->create(['name' => 'Test Carrier']);
    $region = Region::factory()->active()->create(['countries' => []]);
    $shippingRate = ShippingRate::factory()->for($carrier, 'carrier')->for($region)->create([
        'is_active' => true,
        'type' => ShippingRateType::Flat,
        'rate' => '10.0000',
    ]);

    $response = withUnencryptedCookie('cart_id', $cart->id)->post(route('checkout.shipping-options.index'), []);

    $response->assertOk()
        ->assertJsonStructure([
            'shipping' => [
                '*' => ['id', 'name', 'carrier_name', 'type', 'rate', 'delivery_time'],
            ],
            'tax_estimate',
        ])
        ->assertJsonMissingPath('payment')
        ->assertJsonCount(1, 'shipping')
        ->assertJsonPath('shipping.0.id', $shippingRate->id)
        ->assertJsonPath('shipping.0.carrier_name.en', 'Test Carrier')
        ->assertJsonPath('shipping.0.type', ShippingRateType::Flat->value)
        ->assertJsonPath('shipping.0.rate', '10.0000');
});

test('filters shipping options by address', function () {
    $cart = Cart::factory()->create(['subtotal' => '100.0000']);

    $product = Product::factory()->create();
    CartItem::factory()->for($cart)->create([
        'product_id' => $product->id,
        'quantity' => 1,
        'unit_price' => '100.0000',
        'total_price' => '100.0000',
    ]);

    $carrier = ShippingCarrier::factory()->active()->create();
    $region1 = Region::factory()->active()->create(['countries' => ['US']]);
    $region2 = Region::factory()->active()->create(['countries' => ['CA']]);

    $shippingRate1 = ShippingRate::factory()->for($carrier, 'carrier')->for($region1)->active()->create([
        'type' => ShippingRateType::Flat,
        'rate' => '10.0000',
    ]);

    ShippingRate::factory()->for($carrier, 'carrier')->for($region2)->active()->create([
        'type' => ShippingRateType::Flat,
        'rate' => '15.0000',
    ]);

    $response = withUnencryptedCookie('cart_id', $cart->id)->post(route('checkout.shipping-options.index'), [
        'shipping_address' => [
            'country_code' => 'US',
            'state' => 'NY',
            'postal_code' => '10001',
        ],
    ]);

    $response->assertOk()
        ->assertJsonCount(1, 'shipping')
        ->assertJsonPath('shipping.0.id', $shippingRate1->id);
});

test('uses shipping address when billing address not provided', function () {
    $cart = Cart::factory()->create(['subtotal' => '100.0000']);

    $product = Product::factory()->create();
    CartItem::factory()->for($cart)->create([
        'product_id' => $product->id,
        'quantity' => 1,
        'unit_price' => '100.0000',
        'total_price' => '100.0000',
    ]);

    $carrier = ShippingCarrier::factory()->active()->create();
    $region = Region::factory()->active()->create(['countries' => ['US']]);
    $shippingRate = ShippingRate::factory()->for($carrier, 'carrier')->for($region)->active()->create(['rate' => '10.0000']);

    $response = withUnencryptedCookie('cart_id', $cart->id)->post(route('checkout.shipping-options.index'), [
        'shipping_address' => [
            'country_code' => 'US',
            'state' => 'NY',
            'postal_code' => '10001',
        ],
    ]);

    $response->assertOk()
        ->assertJsonCount(1, 'shipping')
        ->assertJsonPath('shipping.0.id', $shippingRate->id);
});

test('ships to the shipping address even when a different billing address is provided', function () {
    $cart = Cart::factory()->create(['subtotal' => '100.0000']);

    $product = Product::factory()->create();
    CartItem::factory()->for($cart)->create([
        'product_id' => $product->id,
        'quantity' => 1,
        'unit_price' => '100.0000',
        'total_price' => '100.0000',
    ]);

    $carrier = ShippingCarrier::factory()->active()->create();
    $usRegion = Region::factory()->active()->create(['countries' => ['US']]);
    $shippingRate = ShippingRate::factory()->for($carrier, 'carrier')->for($usRegion)->active()->create(['rate' => '10.0000']);

    $response = withUnencryptedCookie('cart_id', $cart->id)->post(route('checkout.shipping-options.index'), [
        'different_billing_address' => true,
        'shipping_address' => [
            'country_code' => 'US',
            'state' => 'NY',
            'postal_code' => '10001',
        ],
        'billing_address' => [
            'country_code' => 'CA',
            'state' => 'ON',
            'postal_code' => 'M5H 2N2',
        ],
    ]);

    $response->assertOk()
        ->assertJsonCount(1, 'shipping')
        ->assertJsonPath('shipping.0.id', $shippingRate->id);
});

test('returns shipping options for a cart addressed by route param', function () {
    $cart = Cart::factory()->create(['subtotal' => '75.0000']);

    $product = Product::factory()->create();
    CartItem::factory()->for($cart)->create([
        'product_id' => $product->id,
        'quantity' => 1,
        'unit_price' => '75.0000',
        'total_price' => '75.0000',
    ]);

    $carrier = ShippingCarrier::factory()->active()->create();
    $region = Region::factory()->active()->create(['countries' => []]);
    $shippingRate = ShippingRate::factory()->for($carrier, 'carrier')->for($region)->active()->create(['rate' => '8.0000']);

    $response = withUnencryptedCookie('cart_id', $cart->id)->post(route('checkout.shipping-options.index'));

    $response->assertOk()
        ->assertJsonStructure([
            'shipping' => ['*' => ['id', 'name', 'carrier_name', 'type', 'rate', 'delivery_time']],
            'tax_estimate',
        ])
        ->assertJsonPath('shipping.0.id', $shippingRate->id);
});

test('accepts valid address data', function () {
    $cart = Cart::factory()->create();

    $response = withUnencryptedCookie('cart_id', $cart->id)->post(route('checkout.shipping-options.index'), [
        'shipping_address' => [
            'country_code' => 'US',
            'state' => 'NY',
            'postal_code' => '10001',
        ],
    ]);

    $response->assertOk();
});

test('accepts request without addresses', function () {
    $cart = Cart::factory()->create();

    $response = withUnencryptedCookie('cart_id', $cart->id)->post(route('checkout.shipping-options.index'), []);

    $response->assertOk();
});

test('validates country_code must be 2 characters when shipping_address provided', function () {
    $cart = Cart::factory()->create();

    $response = withUnencryptedCookie('cart_id', $cart->id)->post(route('checkout.shipping-options.index'), [
        'shipping_address' => [
            'country_code' => 'USA',
            'state' => 'NY',
            'postal_code' => '10001',
        ],
    ], ['Accept' => 'application/json']);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['shipping_address.country_code']);
});

test('allows a partial shipping_address with only a country for the options preview', function () {
    $cart = Cart::factory()->create();

    $response = withUnencryptedCookie('cart_id', $cart->id)->post(route('checkout.shipping-options.index'), [
        'shipping_address' => [
            'country_code' => 'US',
        ],
    ], ['Accept' => 'application/json']);

    $response->assertOk()
        ->assertJsonStructure(['shipping', 'tax_estimate']);
});

test('accepts different_billing_address flag', function () {
    $cart = Cart::factory()->create();

    $response = withUnencryptedCookie('cart_id', $cart->id)->post(route('checkout.shipping-options.index'), [
        'different_billing_address' => true,
        'shipping_address' => [
            'country_code' => 'US',
            'state' => 'NY',
            'postal_code' => '10001',
        ],
        'billing_address' => [
            'country_code' => 'CA',
            'state' => 'ON',
            'postal_code' => 'M5H 2N2',
        ],
    ]);

    $response->assertOk();
});

test('validates billing_address country_code format when provided', function () {
    $cart = Cart::factory()->create();

    $response = withUnencryptedCookie('cart_id', $cart->id)->post(route('checkout.shipping-options.index'), [
        'different_billing_address' => true,
        'shipping_address' => [
            'country_code' => 'US',
            'state' => 'NY',
            'postal_code' => '10001',
        ],
        'billing_address' => [
            'country_code' => 'C',
        ],
    ], ['Accept' => 'application/json']);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['billing_address.country_code'])
        ->assertJsonMissingValidationErrors(['billing_address.postal_code']);
});

test('returns tax estimate when address matches a region with active tax rates', function () {
    Setting::setValue('prices_include_tax', false);
    Setting::setValue('tax_based_on', 'shipping');

    $cart = Cart::factory()->create(['subtotal' => '100.0000']);

    $taxCategory = TaxCategory::Standard->value;
    $product = Product::factory()->create([
        'price' => '100.0000',
        'tax_category' => $taxCategory,
        'is_tax_exempt' => false,
    ]);
    CartItem::factory()->for($cart)->create([
        'product_id' => $product->id,
        'quantity' => 1,
        'unit_price' => '100.0000',
        'total_price' => '100.0000',
    ]);

    $region = Region::factory()->active()->create(['countries' => ['US']]);
    TaxRate::factory()->active()->withoutConditions()->create([
        'region_id' => $region->id,
        'tax_category' => $taxCategory,
        'rate' => '10.0000',
        'is_compound' => false,
    ]);

    $response = withUnencryptedCookie('cart_id', $cart->id)->post(route('checkout.shipping-options.index'), [
        'shipping_address' => [
            'country_code' => 'US',
            'state' => 'NY',
            'postal_code' => '10001',
        ],
    ]);

    $response->assertOk()
        ->assertJsonPath('tax_estimate', '10.0000');
});

test('returns zero tax estimate when no tax rates match the address', function () {
    Setting::setValue('prices_include_tax', false);
    Setting::setValue('tax_based_on', 'shipping');

    $cart = Cart::factory()->create(['subtotal' => '100.0000']);

    $product = Product::factory()->create(['price' => '100.0000']);
    CartItem::factory()->for($cart)->create([
        'product_id' => $product->id,
        'quantity' => 1,
        'unit_price' => '100.0000',
        'total_price' => '100.0000',
    ]);

    $region = Region::factory()->active()->create(['countries' => ['US']]);
    TaxRate::factory()->active()->withoutConditions()->create([
        'region_id' => $region->id,
        'rate' => '10.0000',
    ]);

    $response = withUnencryptedCookie('cart_id', $cart->id)->post(route('checkout.shipping-options.index'), [
        'shipping_address' => [
            'country_code' => 'CA',
            'state' => 'ON',
            'postal_code' => 'M5H 2N2',
        ],
    ]);

    $response->assertOk()
        ->assertJsonPath('tax_estimate', '0.0000');
});

test('returns zero tax estimate when no address is provided', function () {
    $cart = Cart::factory()->create(['subtotal' => '100.0000']);

    $product = Product::factory()->create(['price' => '100.0000']);
    CartItem::factory()->for($cart)->create([
        'product_id' => $product->id,
        'quantity' => 1,
        'unit_price' => '100.0000',
        'total_price' => '100.0000',
    ]);

    $response = withUnencryptedCookie('cart_id', $cart->id)->post(route('checkout.shipping-options.index'), []);

    $response->assertOk()
        ->assertJsonPath('tax_estimate', '0.0000');
});
