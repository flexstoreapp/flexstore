<?php

declare(strict_types=1);

use App\Actions\StorePaymentGatewayAction;
use App\Enums\CheckoutMode;
use App\Enums\PaymentGatewayDriver;
use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Enums\WeightUnit;
use App\Http\Controllers\Admin\PaymentGatewayController;
use App\Http\Requests\Admin\StorePaymentGatewayRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\PaymentGateway;
use App\Models\Product;
use App\Models\Region;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\castAsJson;
use function Pest\Laravel\post;

covers(PaymentGatewayController::class, StorePaymentGatewayRequest::class, StorePaymentGatewayAction::class);

uses()->group('payment');

test('creates a new payment gateway', function () {
    $products = Product::factory(3)->create();
    $categories = Category::factory(3)->create();
    $brands = Brand::factory(3)->create();
    $regions = Region::factory(3)->create();

    $data = [
        'name' => 'Cash on Delivery',
        'driver' => PaymentGatewayDriver::Cod->value,
        'credentials' => null,
        'min_order_value' => '100',
        'max_order_value' => '1000',
        'min_weight' => '1.00',
        'min_weight_unit' => WeightUnit::Kg->value,
        'max_weight' => '10.00',
        'max_weight_unit' => WeightUnit::Lb->value,
        'excluded_products' => $products->pluck('id')->all(),
        'excluded_categories' => $categories->pluck('id')->all(),
        'excluded_brands' => $brands->pluck('id')->all(),
        'allowed_regions' => $regions->pluck('id')->all(),
        'sync_external_refunds' => true,
        'is_active' => true,
    ];

    $response = actingAsSuperAdmin()->post(route('admin.payment.gateways.store'), $data);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('payment_gateways', [
        'name' => castAsTranslatableJson('Cash on Delivery'),
        'driver' => PaymentGatewayDriver::Cod->value,
        'credentials' => null,
        'min_order_value' => '100',
        'max_order_value' => '1000',
        'min_weight' => '1.00',
        'min_weight_unit' => WeightUnit::Kg->value,
        'max_weight' => '10.00',
        'max_weight_unit' => WeightUnit::Lb->value,
        'excluded_products' => castAsJson($products->pluck('id')->all()),
        'excluded_categories' => castAsJson($categories->pluck('id')->all()),
        'excluded_brands' => castAsJson($brands->pluck('id')->all()),
        'allowed_regions' => castAsJson($regions->pluck('id')->all()),
        'sync_external_refunds' => true,
        'is_active' => true,
    ]);
});

test('validates required fields', function () {
    $response = actingAsSuperAdmin()->post(route('admin.payment.gateways.store'), [
        'name' => '',
        'driver' => '',
        'is_active' => '',
    ]);

    $response->assertRedirectBack()
        ->assertInvalid(['name', 'driver', 'is_active']);
});

test('validates excluded_brands array contains valid brand IDs', function () {
    $response = actingAsSuperAdmin()->post(route('admin.payment.gateways.store'), [
        'name' => 'Cash on Delivery',
        'driver' => PaymentGatewayDriver::Cod->value,
        'excluded_brands' => [999999],
        'is_active' => true,
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('excluded_brands.0');
});

test('validates name field', function () {
    // Test name max length validation
    $response = actingAsSuperAdmin()->post(route('admin.payment.gateways.store'), [
        'name' => str_repeat('a', 256), // Exceeds max:255
        'driver' => PaymentGatewayDriver::Cod->value,
        'is_active' => true,
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('name');
});

test('validates driver field must be valid enum', function () {
    $response = actingAsSuperAdmin()->post(route('admin.payment.gateways.store'), [
        'name' => 'Valid Name',
        'driver' => 'invalid_driver', // Invalid enum value
        'is_active' => true,
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('driver');
});

test('numeric fields must be positive', function () {
    $response = actingAsSuperAdmin()->post(route('admin.payment.gateways.store'), [
        'name' => 'Valid Name',
        'driver' => PaymentGatewayDriver::Cod->value,
        'min_order_value' => -5, // Negative value
        'max_order_value' => -10, // Negative value
        'min_weight' => -2, // Negative value
        'max_weight' => -8, // Negative value
        'is_active' => true,
    ]);

    $response->assertRedirectBack()
        ->assertInvalid([
            'min_order_value',
            'max_order_value',
            'min_weight',
            'max_weight',
        ]);
});

test('validates max values must be greater than min values', function () {
    $response = actingAsSuperAdmin()->post(route('admin.payment.gateways.store'), [
        'name' => 'Valid Name',
        'driver' => PaymentGatewayDriver::Cod->value,
        'min_order_value' => '100',
        'max_order_value' => '50', // Less than min_order_value
        'min_weight' => '10.00',
        'max_weight' => '5.00', // Less than min_weight
        'is_active' => true,
    ]);

    $response->assertRedirectBack()
        ->assertInvalid(['max_order_value', 'max_weight']);
});

test('validates stripe credentials when driver is stripe', function () {
    $response = actingAsSuperAdmin()->post(route('admin.payment.gateways.store'), [
        'name' => 'Stripe Gateway',
        'driver' => PaymentGatewayDriver::Stripe->value,
        'credentials' => [
            'publishable_key' => '', // Empty required field
            'secret_key' => '', // Empty required field
        ],
        'is_active' => true,
    ]);

    $response->assertRedirectBack()
        ->assertInvalid([
            'credentials.publishable_key',
            'credentials.secret_key',
        ]);
});

test('validates stripe credentials max length', function () {
    $response = actingAsSuperAdmin()->post(route('admin.payment.gateways.store'), [
        'name' => 'Stripe Gateway',
        'driver' => PaymentGatewayDriver::Stripe->value,
        'credentials' => [
            'publishable_key' => str_repeat('a', 256), // Exceeds max:255
            'secret_key' => str_repeat('b', 256), // Exceeds max:255
        ],
        'is_active' => true,
    ]);

    $response->assertRedirectBack()
        ->assertInvalid([
            'credentials.publishable_key',
            'credentials.secret_key',
        ]);
});

test('does not require stripe credentials for non-stripe gateway drivers', function () {
    $response = actingAsSuperAdmin()->post(route('admin.payment.gateways.store'), [
        'name' => 'COD Gateway',
        'driver' => PaymentGatewayDriver::Cod->value,
        'credentials' => null,
        'is_active' => true,
    ]);

    $response->assertValid([
        'credentials.publishable_key',
        'credentials.secret_key',
    ]);
});

test('validates boolean field', function () {
    $response = actingAsSuperAdmin()->post(route('admin.payment.gateways.store'), [
        'name' => 'Valid Name',
        'driver' => PaymentGatewayDriver::Cod->value,
        'is_active' => 'not-a-boolean', // Invalid boolean
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('is_active');
});

test('creates a new payment gateway with credentials', function () {
    $data = [
        'name' => 'Stripe',
        'driver' => PaymentGatewayDriver::Stripe->value,
        'credentials' => [
            'publishable_key' => 'test_publishable_key',
            'secret_key' => 'test_secret_key',
            'signing_secret' => 'whsec_test_123',
        ],
        'is_active' => true,
    ];

    $response = actingAsSuperAdmin()->post(route('admin.payment.gateways.store'), $data);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('payment_gateways', [
        'name' => castAsTranslatableJson('Stripe'),
        'driver' => PaymentGatewayDriver::Stripe->value,
        'is_active' => true,
    ]);

    expect(PaymentGateway::first()->credentials)->toBe([
        'publishable_key' => 'test_publishable_key',
        'secret_key' => 'test_secret_key',
        'signing_secret' => 'whsec_test_123',
    ]);
});

test('validates signing secret max length', function () {
    $response = actingAsSuperAdmin()->post(route('admin.payment.gateways.store'), [
        'name' => 'Stripe Gateway',
        'driver' => PaymentGatewayDriver::Stripe->value,
        'credentials' => [
            'publishable_key' => 'pk_test_123',
            'secret_key' => 'sk_test_123',
            'signing_secret' => str_repeat('a', 256),
        ],
        'is_active' => true,
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('credentials.signing_secret');
});

test('requires authentication', function () {
    $response = post(route('admin.payment.gateways.store'), [
        'name' => 'Test Gateway',
        'driver' => PaymentGatewayDriver::Cod->value,
        'is_active' => true,
    ]);

    $response->assertRedirect(route('admin.login'));
});

test('requires settings.payment.configure permission', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();

    $response = actingAsAdmin()->post(route('admin.payment.gateways.store'), [
        'name' => 'Test Gateway',
        'driver' => PaymentGatewayDriver::Cod->value,
        'is_active' => true,
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('payment_gateways', [
        'name' => castAsTranslatableJson('Test Gateway'),
    ]);

    $role->revokePermissionTo(Permission::SettingsPaymentConfigure);

    $response = actingAsAdmin()->post(route('admin.payment.gateways.store'), [
        'name' => 'Another Gateway',
        'driver' => PaymentGatewayDriver::Cod->value,
        'is_active' => true,
    ]);

    $response->assertForbidden();

    assertDatabaseMissing('payment_gateways', [
        'name' => castAsTranslatableJson('Another Gateway'),
    ]);
});

test('creates stripe gateway with checkout mode', function () {
    $data = [
        'name' => 'Stripe Embedded',
        'driver' => PaymentGatewayDriver::Stripe->value,
        'credentials' => [
            'publishable_key' => 'pk_test_123',
            'secret_key' => 'sk_test_123',
            'checkout_mode' => CheckoutMode::Embedded->value,
        ],
        'is_active' => true,
    ];

    $response = actingAsSuperAdmin()->post(route('admin.payment.gateways.store'), $data);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    $gateway = PaymentGateway::query()->latest('id')->first();
    expect($gateway->credentials['checkout_mode'])->toBe(CheckoutMode::Embedded->value);
});

test('validates checkout mode must be valid enum value', function () {
    $response = actingAsSuperAdmin()->post(route('admin.payment.gateways.store'), [
        'name' => 'Stripe Gateway',
        'driver' => PaymentGatewayDriver::Stripe->value,
        'credentials' => [
            'publishable_key' => 'pk_test_123',
            'secret_key' => 'sk_test_123',
            'checkout_mode' => 'invalid_mode',
        ],
        'is_active' => true,
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('credentials.checkout_mode');
});

test('validates razorpay credentials when driver is razorpay', function () {
    $response = actingAsSuperAdmin()->post(route('admin.payment.gateways.store'), [
        'name' => 'Razorpay Gateway',
        'driver' => PaymentGatewayDriver::Razorpay->value,
        'credentials' => [
            'key_id' => '',
            'key_secret' => '',
        ],
        'is_active' => true,
    ]);

    $response->assertRedirectBack()
        ->assertInvalid([
            'credentials.key_id',
            'credentials.key_secret',
        ]);
});

test('creates razorpay gateway with credentials', function () {
    $data = [
        'name' => 'Razorpay',
        'driver' => PaymentGatewayDriver::Razorpay->value,
        'credentials' => [
            'key_id' => 'rzp_test_123',
            'key_secret' => 'test_secret_456',
            'webhook_secret' => 'whsec_test_789',
        ],
        'is_active' => true,
    ];

    $response = actingAsSuperAdmin()->post(route('admin.payment.gateways.store'), $data);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('payment_gateways', [
        'name' => castAsTranslatableJson('Razorpay'),
        'driver' => PaymentGatewayDriver::Razorpay->value,
        'is_active' => true,
    ]);

    expect(PaymentGateway::query()->latest('id')->first()->credentials)->toBe([
        'key_id' => 'rzp_test_123',
        'key_secret' => 'test_secret_456',
        'webhook_secret' => 'whsec_test_789',
    ]);
});

test('creates razorpay gateway with checkout mode', function () {
    $data = [
        'name' => 'Razorpay Embedded',
        'driver' => PaymentGatewayDriver::Razorpay->value,
        'credentials' => [
            'key_id' => 'rzp_test_123',
            'key_secret' => 'test_secret',
            'checkout_mode' => CheckoutMode::Embedded->value,
        ],
        'is_active' => true,
    ];

    $response = actingAsSuperAdmin()->post(route('admin.payment.gateways.store'), $data);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    $gateway = PaymentGateway::query()->latest('id')->first();
    expect($gateway->credentials['checkout_mode'])->toBe(CheckoutMode::Embedded->value);
});

test('validates razorpay checkout mode must be valid enum value', function () {
    $response = actingAsSuperAdmin()->post(route('admin.payment.gateways.store'), [
        'name' => 'Razorpay Gateway',
        'driver' => PaymentGatewayDriver::Razorpay->value,
        'credentials' => [
            'key_id' => 'rzp_test_123',
            'key_secret' => 'test_secret',
            'checkout_mode' => 'invalid_mode',
        ],
        'is_active' => true,
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('credentials.checkout_mode');
});

test('does not require razorpay credentials for non-razorpay gateway drivers', function () {
    $response = actingAsSuperAdmin()->post(route('admin.payment.gateways.store'), [
        'name' => 'COD Gateway',
        'driver' => PaymentGatewayDriver::Cod->value,
        'credentials' => null,
        'is_active' => true,
    ]);

    $response->assertValid([
        'credentials.key_id',
        'credentials.key_secret',
    ]);
});

test('validates mollie credentials when driver is mollie', function () {
    $response = actingAsSuperAdmin()->post(route('admin.payment.gateways.store'), [
        'name' => 'Mollie Gateway',
        'driver' => PaymentGatewayDriver::Mollie->value,
        'credentials' => [
            'api_key' => '',
        ],
        'is_active' => true,
    ]);

    $response->assertRedirectBack()
        ->assertInvalid(['credentials.api_key']);
});

test('creates mollie gateway with credentials', function () {
    $data = [
        'name' => 'Mollie',
        'driver' => PaymentGatewayDriver::Mollie->value,
        'credentials' => [
            'api_key' => 'test_abc123',
            'profile_id' => 'pfl_xyz789',
        ],
        'is_active' => true,
    ];

    $response = actingAsSuperAdmin()->post(route('admin.payment.gateways.store'), $data);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('payment_gateways', [
        'name' => castAsTranslatableJson('Mollie'),
        'driver' => PaymentGatewayDriver::Mollie->value,
        'is_active' => true,
    ]);

    expect(PaymentGateway::query()->latest('id')->first()->credentials)->toBe([
        'api_key' => 'test_abc123',
        'profile_id' => 'pfl_xyz789',
    ]);
});

test('creates mollie gateway with checkout mode', function () {
    $data = [
        'name' => 'Mollie Embedded',
        'driver' => PaymentGatewayDriver::Mollie->value,
        'credentials' => [
            'api_key' => 'test_abc123',
            'checkout_mode' => CheckoutMode::Embedded->value,
        ],
        'is_active' => true,
    ];

    $response = actingAsSuperAdmin()->post(route('admin.payment.gateways.store'), $data);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    $gateway = PaymentGateway::query()->latest('id')->first();
    expect($gateway->credentials['checkout_mode'])->toBe(CheckoutMode::Embedded->value);
});

test('validates mollie checkout mode must be valid enum value', function () {
    $response = actingAsSuperAdmin()->post(route('admin.payment.gateways.store'), [
        'name' => 'Mollie Gateway',
        'driver' => PaymentGatewayDriver::Mollie->value,
        'credentials' => [
            'api_key' => 'test_abc123',
            'checkout_mode' => 'invalid_mode',
        ],
        'is_active' => true,
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('credentials.checkout_mode');
});

test('does not require mollie credentials for non-mollie gateway drivers', function () {
    $response = actingAsSuperAdmin()->post(route('admin.payment.gateways.store'), [
        'name' => 'COD Gateway',
        'driver' => PaymentGatewayDriver::Cod->value,
        'credentials' => null,
        'is_active' => true,
    ]);

    $response->assertValid([
        'credentials.api_key',
        'credentials.profile_id',
    ]);
});

test('validates tap credentials when driver is tap', function () {
    $response = actingAsSuperAdmin()->post(route('admin.payment.gateways.store'), [
        'name' => 'Tap Gateway',
        'driver' => PaymentGatewayDriver::Tap->value,
        'credentials' => [
            'secret_key' => '',
        ],
        'is_active' => true,
    ]);

    $response->assertRedirectBack()
        ->assertInvalid(['credentials.secret_key']);
});

test('creates tap gateway with credentials and checkout mode', function () {
    $data = [
        'name' => 'Tap',
        'driver' => PaymentGatewayDriver::Tap->value,
        'credentials' => [
            'public_key' => 'pk_test_tap123',
            'secret_key' => 'sk_test_tap789',
            'checkout_mode' => CheckoutMode::Embedded->value,
        ],
        'is_active' => true,
    ];

    $response = actingAsSuperAdmin()->post(route('admin.payment.gateways.store'), $data);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('payment_gateways', [
        'name' => castAsTranslatableJson('Tap'),
        'driver' => PaymentGatewayDriver::Tap->value,
        'is_active' => true,
    ]);

    expect(PaymentGateway::query()->latest('id')->first()->credentials)->toBe([
        'public_key' => 'pk_test_tap123',
        'secret_key' => 'sk_test_tap789',
        'checkout_mode' => CheckoutMode::Embedded->value,
    ]);
});

test('validates paystack credentials when driver is paystack', function () {
    $response = actingAsSuperAdmin()->post(route('admin.payment.gateways.store'), [
        'name' => 'Paystack Gateway',
        'driver' => PaymentGatewayDriver::Paystack->value,
        'credentials' => [
            'secret_key' => '',
        ],
        'is_active' => true,
    ]);

    $response->assertRedirectBack()
        ->assertInvalid(['credentials.secret_key']);
});

test('creates paystack gateway with credentials', function () {
    $data = [
        'name' => 'Paystack',
        'driver' => PaymentGatewayDriver::Paystack->value,
        'credentials' => [
            'public_key' => 'pk_test_abc123',
            'secret_key' => 'sk_test_xyz789',
        ],
        'is_active' => true,
    ];

    $response = actingAsSuperAdmin()->post(route('admin.payment.gateways.store'), $data);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('payment_gateways', [
        'name' => castAsTranslatableJson('Paystack'),
        'driver' => PaymentGatewayDriver::Paystack->value,
        'is_active' => true,
    ]);

    expect(PaymentGateway::query()->latest('id')->first()->credentials)->toBe([
        'public_key' => 'pk_test_abc123',
        'secret_key' => 'sk_test_xyz789',
    ]);
});

test('creates paystack gateway with checkout mode', function () {
    $data = [
        'name' => 'Paystack Embedded',
        'driver' => PaymentGatewayDriver::Paystack->value,
        'credentials' => [
            'secret_key' => 'sk_test_xyz789',
            'checkout_mode' => CheckoutMode::Embedded->value,
        ],
        'is_active' => true,
    ];

    $response = actingAsSuperAdmin()->post(route('admin.payment.gateways.store'), $data);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    $gateway = PaymentGateway::query()->latest('id')->first();
    expect($gateway->credentials['checkout_mode'])->toBe(CheckoutMode::Embedded->value);
});

test('validates paystack checkout mode must be valid enum value', function () {
    $response = actingAsSuperAdmin()->post(route('admin.payment.gateways.store'), [
        'name' => 'Paystack Gateway',
        'driver' => PaymentGatewayDriver::Paystack->value,
        'credentials' => [
            'secret_key' => 'sk_test_xyz789',
            'checkout_mode' => 'invalid_mode',
        ],
        'is_active' => true,
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('credentials.checkout_mode');
});

test('validates mercado pago credentials when driver is mercadopago', function () {
    $response = actingAsSuperAdmin()->post(route('admin.payment.gateways.store'), [
        'name' => 'Mercado Pago Gateway',
        'driver' => PaymentGatewayDriver::MercadoPago->value,
        'credentials' => [
            'access_token' => '',
        ],
        'is_active' => true,
    ]);

    $response->assertRedirectBack()
        ->assertInvalid(['credentials.access_token']);
});

test('creates mercado pago gateway with credentials', function () {
    $data = [
        'name' => 'Mercado Pago',
        'driver' => PaymentGatewayDriver::MercadoPago->value,
        'credentials' => [
            'public_key' => 'APP_USR-public',
            'access_token' => 'APP_USR-token',
            'webhook_secret' => 'whsec_secret',
        ],
        'is_active' => true,
    ];

    $response = actingAsSuperAdmin()->post(route('admin.payment.gateways.store'), $data);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('payment_gateways', [
        'name' => castAsTranslatableJson('Mercado Pago'),
        'driver' => PaymentGatewayDriver::MercadoPago->value,
        'is_active' => true,
    ]);

    expect(PaymentGateway::query()->latest('id')->first()->credentials)->toBe([
        'public_key' => 'APP_USR-public',
        'access_token' => 'APP_USR-token',
        'webhook_secret' => 'whsec_secret',
    ]);
});

test('creates mercado pago gateway with checkout mode', function () {
    $data = [
        'name' => 'Mercado Pago Embedded',
        'driver' => PaymentGatewayDriver::MercadoPago->value,
        'credentials' => [
            'access_token' => 'APP_USR-token',
            'checkout_mode' => CheckoutMode::Embedded->value,
        ],
        'is_active' => true,
    ];

    $response = actingAsSuperAdmin()->post(route('admin.payment.gateways.store'), $data);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    $gateway = PaymentGateway::query()->latest('id')->first();
    expect($gateway->credentials['checkout_mode'])->toBe(CheckoutMode::Embedded->value);
});

test('does not require paystack credentials for non-paystack gateway drivers', function () {
    $response = actingAsSuperAdmin()->post(route('admin.payment.gateways.store'), [
        'name' => 'COD Gateway',
        'driver' => PaymentGatewayDriver::Cod->value,
        'credentials' => null,
        'is_active' => true,
    ]);

    $response->assertValid([
        'credentials.public_key',
        'credentials.secret_key',
    ]);
});
