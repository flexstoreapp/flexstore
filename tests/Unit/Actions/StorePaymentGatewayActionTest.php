<?php

declare(strict_types=1);

use App\Actions\StorePaymentGatewayAction;
use App\DTOs\StorePaymentGatewayInput;
use App\Enums\PaymentGatewayDriver;
use App\Enums\WeightUnit;
use App\Models\PaymentGateway;

covers(StorePaymentGatewayAction::class, StorePaymentGatewayInput::class);

uses()->group('actions', 'payment');

test('creates a payment gateway with all fields', function () {
    $data = [
        'name' => 'Test Gateway',
        'driver' => PaymentGatewayDriver::Cod,
        'credentials' => null,
        'min_order_value' => '100',
        'max_order_value' => '1000',
        'min_weight' => '1.00',
        'min_weight_unit' => WeightUnit::Kg->value,
        'max_weight' => '10.00',
        'max_weight_unit' => WeightUnit::Lb->value,
        'excluded_products' => [1, 2, 3],
        'excluded_categories' => [10, 20, 30],
        'excluded_brands' => [40, 50, 60],
        'allowed_regions' => [100, 200, 300],
        'sync_external_refunds' => false,
        'is_active' => true,
    ];

    $action = new StorePaymentGatewayAction();
    $gateway = $action->handle(StorePaymentGatewayInput::fromArray($data));

    expect($gateway)->toBeInstanceOf(PaymentGateway::class)
        ->and($gateway->name)->toBe('Test Gateway')
        ->and($gateway->sync_external_refunds)->toBeFalse()
        ->and($gateway->driver)->toBe(PaymentGatewayDriver::Cod)
        ->and($gateway->credentials)->toBeNull()
        ->and($gateway->min_order_value)->toBe('100.0000')
        ->and($gateway->max_order_value)->toBe('1000.0000')
        ->and($gateway->min_weight)->toBe('1.00')
        ->and($gateway->min_weight_unit)->toBe(WeightUnit::Kg)
        ->and($gateway->max_weight)->toBe('10.00')
        ->and($gateway->max_weight_unit)->toBe(WeightUnit::Lb)
        ->and($gateway->excluded_products)->toBe([1, 2, 3])
        ->and($gateway->excluded_categories)->toBe([10, 20, 30])
        ->and($gateway->excluded_brands)->toBe([40, 50, 60])
        ->and($gateway->allowed_regions)->toBe([100, 200, 300])
        ->and($gateway->is_active)->toBeTrue();
});

test('creates a payment gateway with credentials', function () {
    $credentials = ['api_key' => 'test_key', 'account_number' => '123456'];
    $data = [
        'name' => 'Stripe',
        'driver' => PaymentGatewayDriver::Stripe,
        'credentials' => $credentials,
        'is_active' => true,
    ];

    $action = new StorePaymentGatewayAction();
    $gateway = $action->handle(StorePaymentGatewayInput::fromArray($data));

    expect($gateway)->toBeInstanceOf(PaymentGateway::class)
        ->and($gateway->name)->toBe('Stripe')
        ->and($gateway->driver)->toBe(PaymentGatewayDriver::Stripe)
        ->and($gateway->credentials)->toBe($credentials)
        ->and($gateway->is_active)->toBeTrue();
});

test('creates an inactive payment gateway', function () {
    $data = [
        'name' => 'Inactive Gateway',
        'driver' => PaymentGatewayDriver::Stripe,
        'is_active' => false,
    ];

    $action = new StorePaymentGatewayAction();
    $gateway = $action->handle(StorePaymentGatewayInput::fromArray($data));

    expect($gateway)->toBeInstanceOf(PaymentGateway::class)
        ->and($gateway->name)->toBe('Inactive Gateway')
        ->and($gateway->driver)->toBe(PaymentGatewayDriver::Stripe)
        ->and($gateway->is_active)->toBeFalse();
});

test('external refund syncing defaults to disabled when not provided', function () {
    $data = [
        'name' => 'Default Sync Gateway',
        'driver' => PaymentGatewayDriver::Stripe,
        'is_active' => true,
    ];

    $action = new StorePaymentGatewayAction();
    $gateway = $action->handle(StorePaymentGatewayInput::fromArray($data));

    expect($gateway->sync_external_refunds)->toBeFalse();
});
