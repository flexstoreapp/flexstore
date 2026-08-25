<?php

declare(strict_types=1);

use App\Actions\UpdatePaymentGatewayAction;
use App\Enums\CheckoutMode;
use App\Enums\PaymentGatewayDriver;
use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Admin\PaymentGatewayController;
use App\Http\Requests\Admin\UpdatePaymentGatewayRequest;
use App\Models\Brand;
use App\Models\PaymentGateway;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\castAsJson;
use function Pest\Laravel\patch;

covers(PaymentGatewayController::class, UpdatePaymentGatewayRequest::class, UpdatePaymentGatewayAction::class);

uses()->group('payment');

test('updates a payment gateway', function () {
    $gateway = PaymentGateway::factory()->create();

    $data = [
        'name' => 'Updated name',
        'driver' => PaymentGatewayDriver::Cod->value,
        'is_active' => false,
    ];

    $response = actingAsSuperAdmin()->patch(route('admin.payment.gateways.update', $gateway), $data);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('payment_gateways', [
        'id' => $gateway->id,
        'name' => castAsTranslatableJson('Updated name'),
        'driver' => PaymentGatewayDriver::Cod->value,
        'is_active' => false,
    ]);
});

test('updates external refund syncing', function () {
    $gateway = PaymentGateway::factory()->stripe()->create(['sync_external_refunds' => true]);

    $response = actingAsSuperAdmin()->patch(route('admin.payment.gateways.update', $gateway), [
        'sync_external_refunds' => false,
    ]);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('payment_gateways', [
        'id' => $gateway->id,
        'sync_external_refunds' => false,
    ]);
});

test('validates required fields', function () {
    $gateway = PaymentGateway::factory()->create();

    $response = actingAsSuperAdmin()->patch(route('admin.payment.gateways.update', $gateway), [
        'name' => '',
        'driver' => '',
        'is_active' => '',
    ]);

    $response->assertRedirectBack()
        ->assertInvalid(['name', 'driver', 'is_active']);
});

test('validates name field', function () {

    $gateway = PaymentGateway::factory()->create();

    $response = actingAsSuperAdmin()->patch(route('admin.payment.gateways.update', $gateway), [
        'name' => str_repeat('a', 256), // Exceeds max:255
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('name');
});

test('validates driver field must be valid enum', function () {
    $gateway = PaymentGateway::factory()->create();

    $response = actingAsSuperAdmin()->patch(route('admin.payment.gateways.update', $gateway), [
        'driver' => 'invalid_driver', // Invalid enum value
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('driver');
});

test('validates numeric fields must be positive', function () {
    $gateway = PaymentGateway::factory()->create();

    $response = actingAsSuperAdmin()->patch(route('admin.payment.gateways.update', $gateway), [
        'min_order_value' => -5, // Negative value
        'max_order_value' => -10, // Negative value
        'min_weight' => -2, // Negative value
        'max_weight' => -8, // Negative value
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
    $gateway = PaymentGateway::factory()->create();

    $response = actingAsSuperAdmin()->patch(route('admin.payment.gateways.update', $gateway), [
        'min_order_value' => '100',
        'max_order_value' => '50', // Less than min_order_value
        'min_weight' => '10.00',
        'max_weight' => '5.00', // Less than min_weight
    ]);

    $response->assertRedirectBack()
        ->assertInvalid(['max_order_value', 'max_weight']);
});

test('validates weight unit fields', function () {
    $gateway = PaymentGateway::factory()->create();

    $response = actingAsSuperAdmin()->patch(route('admin.payment.gateways.update', $gateway), [
        'min_weight' => '10.00',
        'min_weight_unit' => 'invalid_unit', // Invalid weight unit
        'max_weight' => '20.00',
        'max_weight_unit' => 'another_invalid_unit', // Invalid weight unit
    ]);

    $response->assertRedirectBack()
        ->assertInvalid(['min_weight_unit', 'max_weight_unit']);
});

test('validates weight unit is required when weight is provided', function () {
    $gateway = PaymentGateway::factory()->create();

    $response = actingAsSuperAdmin()->patch(route('admin.payment.gateways.update', $gateway), [
        'min_weight' => '10.00',
        // min_weight_unit is missing
        'max_weight' => '20.00',
        // max_weight_unit is missing
    ]);

    $response->assertRedirectBack()
        ->assertInvalid(['min_weight_unit', 'max_weight_unit']);
});

test('validates stripe credentials when driver is stripe', function () {
    $gateway = PaymentGateway::factory()->create();

    $response = actingAsSuperAdmin()->patch(route('admin.payment.gateways.update', $gateway), [
        'driver' => PaymentGatewayDriver::Stripe->value,
        'credentials' => [
            'publishable_key' => '', // Empty required field
            'secret_key' => '', // Empty required field
        ],
    ]);

    $response->assertRedirectBack()
        ->assertInvalid([
            'credentials.publishable_key',
            'credentials.secret_key',
        ]);
});

test('validates stripe credentials max length ', function () {
    $gateway = PaymentGateway::factory()->create();

    $response = actingAsSuperAdmin()->patch(route('admin.payment.gateways.update', $gateway), [
        'driver' => PaymentGatewayDriver::Stripe->value,
        'credentials' => [
            'publishable_key' => str_repeat('a', 256), // Exceeds max:255
            'secret_key' => str_repeat('b', 256), // Exceeds max:255
        ],
    ]);

    $response->assertRedirectBack()
        ->assertInvalid([
            'credentials.publishable_key',
            'credentials.secret_key',
        ]);
});

test('does not require stripe credentials for non-stripe gateway drivers', function () {
    $gateway = PaymentGateway::factory()->create();

    $response = actingAsSuperAdmin()->patch(route('admin.payment.gateways.update', $gateway), [
        'driver' => PaymentGatewayDriver::Cod->value,
        'credentials' => null,
    ]);

    $response->assertValid([
        'credentials.publishable_key',
        'credentials.secret_key',
    ]);
});

test('validates boolean field', function () {
    $gateway = PaymentGateway::factory()->create();

    $response = actingAsSuperAdmin()->patch(route('admin.payment.gateways.update', $gateway), [
        'is_active' => 'not-a-boolean', // Invalid boolean
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('is_active');
});

test('validates excluded_brands array contains valid brand IDs', function () {
    $gateway = PaymentGateway::factory()->create();

    $response = actingAsSuperAdmin()->patch(route('admin.payment.gateways.update', $gateway), [
        'excluded_brands' => [999999],
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('excluded_brands.0');
});

test('updates payment gateway with valid excluded brands', function () {
    $gateway = PaymentGateway::factory()->create();
    $brand = Brand::factory()->create();

    $response = actingAsSuperAdmin()->patch(route('admin.payment.gateways.update', $gateway), [
        'excluded_brands' => [$brand->id],
    ]);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('payment_gateways', [
        'id' => $gateway->id,
        'excluded_brands' => castAsJson([$brand->id]),
    ]);
});

test('updates payment gateway credentials', function () {
    $gateway = PaymentGateway::factory()->stripe()->create();

    $data = [
        'credentials' => [
            'publishable_key' => 'new_key',
            'secret_key' => 'new_secret',
            'signing_secret' => 'whsec_new_secret',
        ],
    ];

    $response = actingAsSuperAdmin()->patch(route('admin.payment.gateways.update', $gateway), $data);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($gateway->refresh()->credentials)->toBe([
        'publishable_key' => 'new_key',
        'secret_key' => 'new_secret',
        'signing_secret' => 'whsec_new_secret',
    ]);
});

test('validates signing secret max length', function () {
    $gateway = PaymentGateway::factory()->stripe()->create();

    $response = actingAsSuperAdmin()->patch(route('admin.payment.gateways.update', $gateway), [
        'driver' => PaymentGatewayDriver::Stripe->value,
        'credentials' => [
            'publishable_key' => 'pk_test_123',
            'secret_key' => 'sk_test_123',
            'signing_secret' => str_repeat('a', 256),
        ],
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('credentials.signing_secret');
});

test('requires authentication', function () {
    $gateway = PaymentGateway::factory()->create();

    $response = patch(route('admin.payment.gateways.update', $gateway), [
        'name' => 'Updated Gateway',
        'driver' => PaymentGatewayDriver::Cod->value,
        'is_active' => true,
    ]);

    $response->assertRedirect(route('admin.login'));
});

test('requires settings.payment.configure permission', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();
    $gateway = PaymentGateway::factory()->create(['driver' => PaymentGatewayDriver::Cod->value]);

    $response = actingAsAdmin()->patch(route('admin.payment.gateways.update', $gateway), [
        'name' => 'Updated Gateway',
        'driver' => PaymentGatewayDriver::Cod->value,
        'is_active' => true,
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('payment_gateways', [
        'id' => $gateway->id,
        'name' => castAsTranslatableJson('Updated Gateway'),
    ]);

    $role->revokePermissionTo(Permission::SettingsPaymentConfigure);

    $anotherGateway = PaymentGateway::factory()->create(['driver' => PaymentGatewayDriver::Stripe->value]);

    $response = actingAsAdmin()->patch(route('admin.payment.gateways.update', $anotherGateway), [
        'name' => 'Another Update',
        'driver' => PaymentGatewayDriver::Stripe->value,
        'is_active' => true,
    ]);

    $response->assertForbidden();

    assertDatabaseMissing('payment_gateways', [
        'name' => castAsTranslatableJson('Another Update'),
    ]);
});

test('updates stripe gateway checkout mode', function () {
    $gateway = PaymentGateway::factory()->stripe()->create();

    $response = actingAsSuperAdmin()->patch(route('admin.payment.gateways.update', $gateway), [
        'credentials' => [
            'publishable_key' => 'pk_test_123',
            'secret_key' => 'sk_test_123',
            'checkout_mode' => CheckoutMode::Embedded->value,
        ],
    ]);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($gateway->refresh()->credentials['checkout_mode'])->toBe(CheckoutMode::Embedded->value);
});

test('validates checkout mode must be valid enum value on update', function () {
    $gateway = PaymentGateway::factory()->stripe()->create();

    $response = actingAsSuperAdmin()->patch(route('admin.payment.gateways.update', $gateway), [
        'driver' => PaymentGatewayDriver::Stripe->value,
        'credentials' => [
            'publishable_key' => 'pk_test_123',
            'secret_key' => 'sk_test_123',
            'checkout_mode' => 'invalid_mode',
        ],
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('credentials.checkout_mode');
});

test('validates razorpay credentials when driver is razorpay', function () {
    $gateway = PaymentGateway::factory()->create();

    $response = actingAsSuperAdmin()->patch(route('admin.payment.gateways.update', $gateway), [
        'driver' => PaymentGatewayDriver::Razorpay->value,
        'credentials' => [
            'key_id' => '',
            'key_secret' => '',
        ],
    ]);

    $response->assertRedirectBack()
        ->assertInvalid([
            'credentials.key_id',
            'credentials.key_secret',
        ]);
});

test('updates razorpay gateway credentials', function () {
    $gateway = PaymentGateway::factory()->razorpay()->create();

    $data = [
        'credentials' => [
            'key_id' => 'rzp_live_new_key',
            'key_secret' => 'new_secret',
            'webhook_secret' => 'whsec_new_secret',
        ],
    ];

    $response = actingAsSuperAdmin()->patch(route('admin.payment.gateways.update', $gateway), $data);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($gateway->refresh()->credentials)->toBe([
        'key_id' => 'rzp_live_new_key',
        'key_secret' => 'new_secret',
        'webhook_secret' => 'whsec_new_secret',
    ]);
});

test('updates razorpay gateway checkout mode', function () {
    $gateway = PaymentGateway::factory()->razorpay()->create();

    $response = actingAsSuperAdmin()->patch(route('admin.payment.gateways.update', $gateway), [
        'credentials' => [
            'key_id' => 'rzp_test_123',
            'key_secret' => 'test_secret',
            'checkout_mode' => CheckoutMode::Embedded->value,
        ],
    ]);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($gateway->refresh()->credentials['checkout_mode'])->toBe(CheckoutMode::Embedded->value);
});

test('validates razorpay checkout mode must be valid enum value on update', function () {
    $gateway = PaymentGateway::factory()->razorpay()->create();

    $response = actingAsSuperAdmin()->patch(route('admin.payment.gateways.update', $gateway), [
        'driver' => PaymentGatewayDriver::Razorpay->value,
        'credentials' => [
            'key_id' => 'rzp_test_123',
            'key_secret' => 'test_secret',
            'checkout_mode' => 'invalid_mode',
        ],
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('credentials.checkout_mode');
});

test('validates mollie credentials when driver is mollie', function () {
    $gateway = PaymentGateway::factory()->create();

    $response = actingAsSuperAdmin()->patch(route('admin.payment.gateways.update', $gateway), [
        'driver' => PaymentGatewayDriver::Mollie->value,
        'credentials' => [
            'api_key' => '',
        ],
    ]);

    $response->assertRedirectBack()
        ->assertInvalid(['credentials.api_key']);
});

test('updates mollie gateway credentials', function () {
    $gateway = PaymentGateway::factory()->mollie()->create();

    $data = [
        'credentials' => [
            'api_key' => 'live_new_key_123',
            'profile_id' => 'pfl_new_profile',
        ],
    ];

    $response = actingAsSuperAdmin()->patch(route('admin.payment.gateways.update', $gateway), $data);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($gateway->refresh()->credentials)->toBe([
        'api_key' => 'live_new_key_123',
        'profile_id' => 'pfl_new_profile',
    ]);
});

test('updates mollie gateway checkout mode', function () {
    $gateway = PaymentGateway::factory()->mollie()->create();

    $response = actingAsSuperAdmin()->patch(route('admin.payment.gateways.update', $gateway), [
        'credentials' => [
            'api_key' => 'test_abc123',
            'checkout_mode' => CheckoutMode::Embedded->value,
        ],
    ]);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($gateway->refresh()->credentials['checkout_mode'])->toBe(CheckoutMode::Embedded->value);
});

test('validates mollie checkout mode must be valid enum value on update', function () {
    $gateway = PaymentGateway::factory()->mollie()->create();

    $response = actingAsSuperAdmin()->patch(route('admin.payment.gateways.update', $gateway), [
        'driver' => PaymentGatewayDriver::Mollie->value,
        'credentials' => [
            'api_key' => 'test_abc123',
            'checkout_mode' => 'invalid_mode',
        ],
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('credentials.checkout_mode');
});
