<?php

declare(strict_types=1);

use App\DTOs\AddressLocation;
use App\DTOs\OrderItemsSummary;
use App\Enums\CheckoutMode;
use App\Models\Brand;
use App\Models\PaymentGateway;
use App\Models\Product;
use App\Models\Region;
use App\Queries\EligiblePaymentOptionsQuery;

covers(EligiblePaymentOptionsQuery::class);

uses()->group('queries', 'checkout');

test('excludes region-constrained gateways when address is null', function () {
    $region = Region::factory()->create(['countries' => ['US']]);
    $product = Product::factory()->create(['price' => '100.0000']);

    PaymentGateway::factory()->cod()->active()->create([
        'allowed_regions' => [$region->id],
    ]);

    $items = [['product_id' => $product->id, 'product_variant_id' => null, 'quantity' => 1]];

    $query = app(EligiblePaymentOptionsQuery::class);
    $result = $query->execute(OrderItemsSummary::fromItems($items));

    expect($result)->toHaveCount(0);
});

test('excludes region-constrained gateways when country_code is null', function () {
    $region = Region::factory()->create(['countries' => ['US']]);
    $product = Product::factory()->create(['price' => '100.0000']);

    PaymentGateway::factory()->cod()->active()->create([
        'allowed_regions' => [$region->id],
    ]);

    $items = [['product_id' => $product->id, 'product_variant_id' => null, 'quantity' => 1]];

    $query = app(EligiblePaymentOptionsQuery::class);
    $result = $query->execute(OrderItemsSummary::fromItems($items), AddressLocation::fromArray(['country_code' => null]));

    expect($result)->toHaveCount(0);
});

test('includes region-constrained gateways when country matches', function () {
    $region = Region::factory()->create(['countries' => ['US']]);
    $product = Product::factory()->create(['price' => '100.0000']);

    $gateway = PaymentGateway::factory()->cod()->active()->create([
        'allowed_regions' => [$region->id],
    ]);

    $items = [['product_id' => $product->id, 'product_variant_id' => null, 'quantity' => 1]];

    $query = app(EligiblePaymentOptionsQuery::class);
    $result = $query->execute(OrderItemsSummary::fromItems($items), AddressLocation::fromArray(['country_code' => 'US']));

    expect($result)->toHaveCount(1)
        ->and($result->first()['id'])->toBe($gateway->id);
});

test('includes gateways without region constraints when address is null', function () {
    $product = Product::factory()->create(['price' => '100.0000']);

    $gateway = PaymentGateway::factory()->cod()->active()->create([
        'allowed_regions' => [],
    ]);

    $items = [['product_id' => $product->id, 'product_variant_id' => null, 'quantity' => 1]];

    $query = app(EligiblePaymentOptionsQuery::class);
    $result = $query->execute(OrderItemsSummary::fromItems($items));

    expect($result)->toHaveCount(1)
        ->and($result->first()['id'])->toBe($gateway->id);
});

test('excludes gateways when country does not match region', function () {
    $region = Region::factory()->create(['countries' => ['US']]);
    $product = Product::factory()->create(['price' => '100.0000']);

    PaymentGateway::factory()->cod()->active()->create([
        'allowed_regions' => [$region->id],
    ]);

    $items = [['product_id' => $product->id, 'product_variant_id' => null, 'quantity' => 1]];

    $query = app(EligiblePaymentOptionsQuery::class);
    $result = $query->execute(OrderItemsSummary::fromItems($items), AddressLocation::fromArray(['country_code' => 'CA']));

    expect($result)->toHaveCount(0);
});

test('excludes gateways when state is null but region requires states', function () {
    $region = Region::factory()->create(['countries' => [], 'states' => ['NY']]);
    $product = Product::factory()->create(['price' => '100.0000']);

    PaymentGateway::factory()->cod()->active()->create([
        'allowed_regions' => [$region->id],
    ]);

    $items = [['product_id' => $product->id, 'product_variant_id' => null, 'quantity' => 1]];

    $query = app(EligiblePaymentOptionsQuery::class);
    $result = $query->execute(OrderItemsSummary::fromItems($items), AddressLocation::fromArray(['country_code' => 'US', 'state' => null]));

    expect($result)->toHaveCount(0);
});

test('matches gateways when postal_code matches a wildcard or range pattern', function () {
    $region = Region::factory()->create(['countries' => [], 'states' => [], 'postal_codes' => ['902*', '60601..60699']]);
    $product = Product::factory()->create(['price' => '100.0000']);

    $gateway = PaymentGateway::factory()->cod()->active()->create([
        'allowed_regions' => [$region->id],
    ]);

    $items = [['product_id' => $product->id, 'product_variant_id' => null, 'quantity' => 1]];

    $query = app(EligiblePaymentOptionsQuery::class);

    expect($query->execute(OrderItemsSummary::fromItems($items), AddressLocation::fromArray(['postal_code' => '90250'])))
        ->toHaveCount(1)
        ->and($query->execute(OrderItemsSummary::fromItems($items), AddressLocation::fromArray(['postal_code' => '60650']))->first()['id'])
        ->toBe($gateway->id);

    expect($query->execute(OrderItemsSummary::fromItems($items), AddressLocation::fromArray(['postal_code' => '30303'])))
        ->toHaveCount(0);
});

test('returns embedded checkout fields for stripe gateway with embedded mode', function () {
    $product = Product::factory()->create(['price' => '100.0000']);

    PaymentGateway::factory()->stripe()->active()->create([
        'credentials' => [
            'publishable_key' => 'pk_test_abc123',
            'secret_key' => 'sk_test_abc123',
            'checkout_mode' => CheckoutMode::Embedded->value,
        ],
    ]);

    $items = [['product_id' => $product->id, 'product_variant_id' => null, 'quantity' => 1]];

    $query = app(EligiblePaymentOptionsQuery::class);
    $result = $query->execute(OrderItemsSummary::fromItems($items));

    expect($result)->toHaveCount(1)
        ->and($result->first()['checkout_mode'])->toBe(CheckoutMode::Embedded->value)
        ->and($result->first()['publishable_key'])->toBe('pk_test_abc123');
});

test('does not return embedded fields for stripe gateway with hosted mode', function () {
    $product = Product::factory()->create(['price' => '100.0000']);

    PaymentGateway::factory()->stripe()->active()->create([
        'credentials' => [
            'publishable_key' => 'pk_test_abc123',
            'secret_key' => 'sk_test_abc123',
            'checkout_mode' => CheckoutMode::Hosted->value,
        ],
    ]);

    $items = [['product_id' => $product->id, 'product_variant_id' => null, 'quantity' => 1]];

    $query = app(EligiblePaymentOptionsQuery::class);
    $result = $query->execute(OrderItemsSummary::fromItems($items));

    expect($result)->toHaveCount(1)
        ->and($result->first())->not->toHaveKey('checkout_mode')
        ->and($result->first())->not->toHaveKey('publishable_key');
});

test('does not return embedded fields for non-stripe gateways', function () {
    $product = Product::factory()->create(['price' => '100.0000']);

    PaymentGateway::factory()->cod()->active()->create();

    $items = [['product_id' => $product->id, 'product_variant_id' => null, 'quantity' => 1]];

    $query = app(EligiblePaymentOptionsQuery::class);
    $result = $query->execute(OrderItemsSummary::fromItems($items));

    expect($result)->toHaveCount(1)
        ->and($result->first())->not->toHaveKey('checkout_mode')
        ->and($result->first())->not->toHaveKey('publishable_key');
});

test('returns embedded checkout fields for mollie gateway with embedded mode', function () {
    $product = Product::factory()->create(['price' => '100.0000']);

    PaymentGateway::factory()->mollie()->active()->create([
        'credentials' => [
            'api_key' => 'test_mollie_abc123',
            'profile_id' => 'pfl_test_xyz',
            'checkout_mode' => CheckoutMode::Embedded->value,
        ],
    ]);

    $items = [['product_id' => $product->id, 'product_variant_id' => null, 'quantity' => 1]];

    $query = app(EligiblePaymentOptionsQuery::class);
    $result = $query->execute(OrderItemsSummary::fromItems($items));

    expect($result)->toHaveCount(1)
        ->and($result->first()['checkout_mode'])->toBe(CheckoutMode::Embedded->value)
        ->and($result->first()['profile_id'])->toBe('pfl_test_xyz')
        ->and($result->first()['testmode'])->toBeTrue();
});

test('mollie testmode is false for live API key', function () {
    $product = Product::factory()->create(['price' => '100.0000']);

    PaymentGateway::factory()->mollie()->active()->create([
        'credentials' => [
            'api_key' => 'live_mollie_abc123',
            'profile_id' => 'pfl_live_xyz',
            'checkout_mode' => CheckoutMode::Embedded->value,
        ],
    ]);

    $items = [['product_id' => $product->id, 'product_variant_id' => null, 'quantity' => 1]];

    $query = app(EligiblePaymentOptionsQuery::class);
    $result = $query->execute(OrderItemsSummary::fromItems($items));

    expect($result)->toHaveCount(1)
        ->and($result->first()['testmode'])->toBeFalse();
});

test('does not return embedded fields for mollie gateway with hosted mode', function () {
    $product = Product::factory()->create(['price' => '100.0000']);

    PaymentGateway::factory()->mollie()->active()->create([
        'credentials' => [
            'api_key' => 'test_mollie_hosted',
            'profile_id' => 'pfl_test_hosted',
            'checkout_mode' => CheckoutMode::Hosted->value,
        ],
    ]);

    $items = [['product_id' => $product->id, 'product_variant_id' => null, 'quantity' => 1]];

    $query = app(EligiblePaymentOptionsQuery::class);
    $result = $query->execute(OrderItemsSummary::fromItems($items));

    expect($result)->toHaveCount(1)
        ->and($result->first())->not->toHaveKey('checkout_mode')
        ->and($result->first())->not->toHaveKey('profile_id')
        ->and($result->first())->not->toHaveKey('testmode');
});

test('returns embedded checkout fields for paystack gateway with embedded mode', function () {
    $product = Product::factory()->create(['price' => '100.0000']);

    PaymentGateway::factory()->paystack()->active()->create([
        'credentials' => [
            'public_key' => 'pk_test_paystack_abc',
            'secret_key' => 'sk_test_paystack_abc',
            'checkout_mode' => CheckoutMode::Embedded->value,
        ],
    ]);

    $items = [['product_id' => $product->id, 'product_variant_id' => null, 'quantity' => 1]];

    $query = app(EligiblePaymentOptionsQuery::class);
    $result = $query->execute(OrderItemsSummary::fromItems($items));

    expect($result)->toHaveCount(1)
        ->and($result->first()['checkout_mode'])->toBe(CheckoutMode::Embedded->value)
        ->and($result->first()['public_key'])->toBe('pk_test_paystack_abc')
        ->and($result->first())->not->toHaveKey('secret_key');
});

test('returns embedded checkout fields for tap gateway with embedded mode', function () {
    $product = Product::factory()->create(['price' => '100.0000']);

    PaymentGateway::factory()->tap()->active()->create([
        'credentials' => [
            'public_key' => 'pk_test_tap_abc',
            'secret_key' => 'sk_test_tap_abc',
            'checkout_mode' => CheckoutMode::Embedded->value,
        ],
    ]);

    $items = [['product_id' => $product->id, 'product_variant_id' => null, 'quantity' => 1]];

    $query = app(EligiblePaymentOptionsQuery::class);
    $result = $query->execute(OrderItemsSummary::fromItems($items));

    expect($result)->toHaveCount(1)
        ->and($result->first()['checkout_mode'])->toBe(CheckoutMode::Embedded->value)
        ->and($result->first()['public_key'])->toBe('pk_test_tap_abc')
        ->and($result->first())->not->toHaveKey('secret_key');
});

test('returns embedded checkout fields for mercado pago gateway with embedded mode', function () {
    $product = Product::factory()->create(['price' => '100.0000']);

    PaymentGateway::factory()->mercadoPago()->active()->create([
        'credentials' => [
            'public_key' => 'APP_USR-public',
            'access_token' => 'APP_USR-token',
            'checkout_mode' => CheckoutMode::Embedded->value,
        ],
    ]);

    $items = [['product_id' => $product->id, 'product_variant_id' => null, 'quantity' => 1]];

    $query = app(EligiblePaymentOptionsQuery::class);
    $result = $query->execute(OrderItemsSummary::fromItems($items));

    expect($result)->toHaveCount(1)
        ->and($result->first()['checkout_mode'])->toBe(CheckoutMode::Embedded->value)
        ->and($result->first()['public_key'])->toBe('APP_USR-public')
        ->and($result->first())->not->toHaveKey('access_token');
});

test('does not return embedded fields for mercado pago gateway with hosted mode', function () {
    $product = Product::factory()->create(['price' => '100.0000']);

    PaymentGateway::factory()->mercadoPago()->active()->create([
        'credentials' => [
            'public_key' => 'APP_USR-public',
            'access_token' => 'APP_USR-token',
            'checkout_mode' => CheckoutMode::Hosted->value,
        ],
    ]);

    $items = [['product_id' => $product->id, 'product_variant_id' => null, 'quantity' => 1]];

    $query = app(EligiblePaymentOptionsQuery::class);
    $result = $query->execute(OrderItemsSummary::fromItems($items));

    expect($result)->toHaveCount(1)
        ->and($result->first())->not->toHaveKey('checkout_mode')
        ->and($result->first())->not->toHaveKey('public_key');
});

test('does not return embedded fields for paystack gateway with hosted mode', function () {
    $product = Product::factory()->create(['price' => '100.0000']);

    PaymentGateway::factory()->paystack()->active()->create([
        'credentials' => [
            'public_key' => 'pk_test_paystack_hosted',
            'secret_key' => 'sk_test_paystack_hosted',
            'checkout_mode' => CheckoutMode::Hosted->value,
        ],
    ]);

    $items = [['product_id' => $product->id, 'product_variant_id' => null, 'quantity' => 1]];

    $query = app(EligiblePaymentOptionsQuery::class);
    $result = $query->execute(OrderItemsSummary::fromItems($items));

    expect($result)->toHaveCount(1)
        ->and($result->first())->not->toHaveKey('checkout_mode')
        ->and($result->first())->not->toHaveKey('public_key');
});

test('excludes gateways whose min weight is above order weight', function () {
    $product = Product::factory()->create([
        'price' => '100.0000',
        'weight' => '0.5',
        'weight_unit' => App\Enums\WeightUnit::Kg,
    ]);

    PaymentGateway::factory()->cod()->active()->create([
        'min_weight' => '2.0',
        'min_weight_unit' => App\Enums\WeightUnit::Kg,
    ]);

    $items = [['product_id' => $product->id, 'product_variant_id' => null, 'quantity' => 1]];

    $result = app(EligiblePaymentOptionsQuery::class)->execute(OrderItemsSummary::fromItems($items));

    expect($result)->toHaveCount(0);
});

test('excludes gateways whose max weight is below order weight', function () {
    $product = Product::factory()->create([
        'price' => '100.0000',
        'weight' => '5.0',
        'weight_unit' => App\Enums\WeightUnit::Kg,
    ]);

    PaymentGateway::factory()->cod()->active()->create([
        'max_weight' => '1.0',
        'max_weight_unit' => App\Enums\WeightUnit::Kg,
    ]);

    $items = [['product_id' => $product->id, 'product_variant_id' => null, 'quantity' => 1]];

    $result = app(EligiblePaymentOptionsQuery::class)->execute(OrderItemsSummary::fromItems($items));

    expect($result)->toHaveCount(0);
});

test('includes gateways when weight falls within min and max constraints', function () {
    $product = Product::factory()->create([
        'price' => '100.0000',
        'weight' => '2.0',
        'weight_unit' => App\Enums\WeightUnit::Kg,
    ]);

    PaymentGateway::factory()->cod()->active()->create([
        'min_weight' => '1.0',
        'min_weight_unit' => App\Enums\WeightUnit::Kg,
        'max_weight' => '5.0',
        'max_weight_unit' => App\Enums\WeightUnit::Kg,
    ]);

    $items = [['product_id' => $product->id, 'product_variant_id' => null, 'quantity' => 1]];

    $result = app(EligiblePaymentOptionsQuery::class)->execute(OrderItemsSummary::fromItems($items));

    expect($result)->toHaveCount(1);
});

test('excludes gateways whose excluded_categories match cart categories', function () {
    $category = App\Models\Category::factory()->create();
    $product = Product::factory()->create(['price' => '100.0000', 'category_id' => $category->id]);

    PaymentGateway::factory()->cod()->active()->create([
        'excluded_categories' => [$category->id],
    ]);

    $items = [['product_id' => $product->id, 'product_variant_id' => null, 'quantity' => 1]];

    $result = app(EligiblePaymentOptionsQuery::class)->execute(OrderItemsSummary::fromItems($items));

    expect($result)->toHaveCount(0);
});

test('excludes gateways whose excluded_brands match cart brands', function () {
    $brand = Brand::factory()->create();
    $product = Product::factory()->create(['price' => '100.0000', 'brand_id' => $brand->id]);

    PaymentGateway::factory()->cod()->active()->create([
        'excluded_brands' => [$brand->id],
    ]);

    $items = [['product_id' => $product->id, 'product_variant_id' => null, 'quantity' => 1]];

    $result = app(EligiblePaymentOptionsQuery::class)->execute(OrderItemsSummary::fromItems($items));

    expect($result)->toHaveCount(0);
});

test('returns embedded checkout fields for paypal gateway with embedded mode', function () {
    $product = Product::factory()->create(['price' => '100.0000']);

    PaymentGateway::factory()->paypal()->active()->create([
        'credentials' => [
            'client_id' => 'paypal_client_abc',
            'client_secret' => 'paypal_secret',
            'checkout_mode' => CheckoutMode::Embedded->value,
        ],
    ]);

    $items = [['product_id' => $product->id, 'product_variant_id' => null, 'quantity' => 1]];

    $result = app(EligiblePaymentOptionsQuery::class)->execute(OrderItemsSummary::fromItems($items));

    expect($result)->toHaveCount(1)
        ->and($result->first()['checkout_mode'])->toBe(CheckoutMode::Embedded->value)
        ->and($result->first()['client_id'])->toBe('paypal_client_abc');
});

test('does not return embedded fields for paypal gateway with hosted mode', function () {
    $product = Product::factory()->create(['price' => '100.0000']);

    PaymentGateway::factory()->paypal()->active()->create([
        'credentials' => [
            'client_id' => 'paypal_client_hosted',
            'client_secret' => 'paypal_secret',
            'checkout_mode' => CheckoutMode::Hosted->value,
        ],
    ]);

    $items = [['product_id' => $product->id, 'product_variant_id' => null, 'quantity' => 1]];

    $result = app(EligiblePaymentOptionsQuery::class)->execute(OrderItemsSummary::fromItems($items));

    expect($result)->toHaveCount(1)
        ->and($result->first())->not->toHaveKey('checkout_mode');
});

test('returns embedded checkout fields for razorpay gateway with embedded mode', function () {
    $product = Product::factory()->create(['price' => '100.0000']);

    PaymentGateway::factory()->razorpay()->active()->create([
        'credentials' => [
            'key_id' => 'rzp_test_123',
            'key_secret' => 'secret',
            'checkout_mode' => CheckoutMode::Embedded->value,
        ],
    ]);

    $items = [['product_id' => $product->id, 'product_variant_id' => null, 'quantity' => 1]];

    $result = app(EligiblePaymentOptionsQuery::class)->execute(OrderItemsSummary::fromItems($items));

    expect($result)->toHaveCount(1)
        ->and($result->first()['checkout_mode'])->toBe(CheckoutMode::Embedded->value)
        ->and($result->first()['key_id'])->toBe('rzp_test_123');
});

test('does not return embedded fields for razorpay gateway with hosted mode', function () {
    $product = Product::factory()->create(['price' => '100.0000']);

    PaymentGateway::factory()->razorpay()->active()->create([
        'credentials' => [
            'key_id' => 'rzp_test_hosted',
            'key_secret' => 'secret',
            'checkout_mode' => CheckoutMode::Hosted->value,
        ],
    ]);

    $items = [['product_id' => $product->id, 'product_variant_id' => null, 'quantity' => 1]];

    $result = app(EligiblePaymentOptionsQuery::class)->execute(OrderItemsSummary::fromItems($items));

    expect($result)->toHaveCount(1)
        ->and($result->first())->not->toHaveKey('checkout_mode');
});

test('includes COD gateways for a physical cart', function () {
    $product = Product::factory()->create(['price' => '100.0000']);

    $gateway = PaymentGateway::factory()->cod()->active()->create();

    $items = [['product_id' => $product->id, 'product_variant_id' => null, 'quantity' => 1]];

    $result = app(EligiblePaymentOptionsQuery::class)
        ->execute(OrderItemsSummary::fromItems($items), AddressLocation::fromArray(['country_code' => 'US']));

    expect($result->pluck('id'))->toContain($gateway->id);
});
