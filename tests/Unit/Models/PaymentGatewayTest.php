<?php

declare(strict_types=1);

use App\Enums\PaymentGatewayDriver;
use App\Enums\WeightUnit;
use App\Models\PaymentGateway;
use Illuminate\Database\Eloquent\Factories\Factory;

uses()->group('models', 'payment');

test('has factory', function () {
    expect(PaymentGateway::factory())->toBeInstanceOf(Factory::class);
});

test('casts attributes correctly', function () {
    $paymentGateway = PaymentGateway::factory()->create([
        'min_order_value' => '10.0000',
        'max_order_value' => '500.0000',
        'min_weight' => '0.50',
        'max_weight' => '25.00',
    ]);

    $casts = $paymentGateway->casts();

    expect($casts)
        ->toBeArray()
        ->toHaveKey('driver', PaymentGatewayDriver::class)
        ->toHaveKey('min_order_value', 'decimal:4')
        ->toHaveKey('max_order_value', 'decimal:4')
        ->toHaveKey('min_weight', 'decimal:2')
        ->toHaveKey('max_weight', 'decimal:2')
        ->toHaveKey('min_weight_unit', WeightUnit::class)
        ->toHaveKey('max_weight_unit', WeightUnit::class)
        ->toHaveKey('credentials', 'encrypted:array')
        ->toHaveKey('excluded_products', 'array')
        ->toHaveKey('excluded_categories', 'array')
        ->toHaveKey('excluded_brands', 'array')
        ->toHaveKey('allowed_regions', 'array')
        ->toHaveKey('is_active', 'boolean');
});

test('payment gateway casts attributes correctly', function () {
    $credentials = ['publishable_key' => 'test_key', 'secret_key' => 'test_secret'];
    $paymentGateway = PaymentGateway::create([
        'name' => 'Stripe',
        'driver' => PaymentGatewayDriver::Stripe,
        'credentials' => $credentials,
        'is_active' => true,
    ]);

    expect($paymentGateway->driver)
        ->toBeInstanceOf(PaymentGatewayDriver::class)
        ->toBe(PaymentGatewayDriver::Stripe);

    expect($paymentGateway->credentials)
        ->toBeArray()
        ->toBe($credentials);

    expect($paymentGateway->is_active)
        ->toBeBool()
        ->toBeTrue();
});
