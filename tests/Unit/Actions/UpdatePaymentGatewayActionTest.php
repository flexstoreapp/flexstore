<?php

declare(strict_types=1);

use App\Actions\UpdatePaymentGatewayAction;
use App\DTOs\UpdatePaymentGatewayInput;
use App\Enums\PaymentGatewayDriver;
use App\Enums\WeightUnit;
use App\Models\PaymentGateway;

covers(UpdatePaymentGatewayAction::class, UpdatePaymentGatewayInput::class);

uses()->group('actions', 'payment');

test('updates a payment gateway', function () {
    $paymentGateway = PaymentGateway::factory()->active()->create();

    $data = [
        'name' => 'New name',
        'driver' => PaymentGatewayDriver::Stripe,
        'credentials' => ['api_key' => 'test_key'],
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
        'is_active' => true,
    ];

    $action = new UpdatePaymentGatewayAction();
    $updatedGateway = $action->handle($paymentGateway->fresh(), UpdatePaymentGatewayInput::fromArray($data));

    expect($updatedGateway)->toBeInstanceOf(PaymentGateway::class)
        ->and($updatedGateway->id)->toBe($paymentGateway->id)
        ->and($updatedGateway->name)->toBe('New name')
        ->and($updatedGateway->driver)->toBe(PaymentGatewayDriver::Stripe)
        ->and($updatedGateway->credentials)->toBe(['api_key' => 'test_key'])
        ->and($updatedGateway->min_order_value)->toBe('100.0000')
        ->and($updatedGateway->max_order_value)->toBe('1000.0000')
        ->and($updatedGateway->min_weight)->toBe('1.00')
        ->and($updatedGateway->min_weight_unit)->toBe(WeightUnit::Kg)
        ->and($updatedGateway->max_weight)->toBe('10.00')
        ->and($updatedGateway->max_weight_unit)->toBe(WeightUnit::Lb)
        ->and($updatedGateway->excluded_products)->toBe([1, 2, 3])
        ->and($updatedGateway->excluded_categories)->toBe([10, 20, 30])
        ->and($updatedGateway->excluded_brands)->toBe([40, 50, 60])
        ->and($updatedGateway->allowed_regions)->toBe([100, 200, 300])
        ->and($updatedGateway->is_active)->toBeTrue();
});

test('updates a payment gateway with credentials', function () {
    $paymentGateway = PaymentGateway::create([
        'name' => 'Stripe',
        'driver' => PaymentGatewayDriver::Stripe,
        'credentials' => null,
        'is_active' => true,
    ]);

    $data = [
        'name' => 'Stripe',
        'driver' => PaymentGatewayDriver::Stripe,
        'credentials' => ['api_key' => 'test_key'],
        'is_active' => true,
    ];

    $action = new UpdatePaymentGatewayAction();
    $updatedGateway = $action->handle($paymentGateway->fresh(), UpdatePaymentGatewayInput::fromArray($data));

    expect($updatedGateway)->toBeInstanceOf(PaymentGateway::class)
        ->and($updatedGateway->id)->toBe($paymentGateway->id)
        ->and($updatedGateway->name)->toBe('Stripe')
        ->and($updatedGateway->driver)->toBe(PaymentGatewayDriver::Stripe)
        ->and($updatedGateway->credentials)->toBe(['api_key' => 'test_key'])
        ->and($updatedGateway->is_active)->toBeTrue();
});

test('marks a payment gateway inactive', function () {
    $paymentGateway = PaymentGateway::create([
        'name' => 'Manual payment',
        'driver' => PaymentGatewayDriver::Cod,
        'is_active' => true,
    ]);

    $data = [
        'is_active' => false,
    ];

    $action = new UpdatePaymentGatewayAction();
    $updatedGateway = $action->handle($paymentGateway->fresh(), UpdatePaymentGatewayInput::fromArray($data));

    expect($updatedGateway->id)->toBe($paymentGateway->id)
        ->and($updatedGateway->is_active)->toBeFalse();
});

test('toggles external refund syncing', function () {
    $paymentGateway = PaymentGateway::factory()->stripe()->create(['sync_external_refunds' => true]);

    $action = new UpdatePaymentGatewayAction();
    $updatedGateway = $action->handle($paymentGateway->fresh(), UpdatePaymentGatewayInput::fromArray([
        'sync_external_refunds' => false,
    ]));

    expect($updatedGateway->sync_external_refunds)->toBeFalse();
});

test('leaves external refund syncing unchanged when not provided', function () {
    $paymentGateway = PaymentGateway::factory()->stripe()->create(['sync_external_refunds' => false]);

    $action = new UpdatePaymentGatewayAction();
    $updatedGateway = $action->handle($paymentGateway->fresh(), UpdatePaymentGatewayInput::fromArray([
        'name' => 'Renamed',
    ]));

    expect($updatedGateway->sync_external_refunds)->toBeFalse();
});
