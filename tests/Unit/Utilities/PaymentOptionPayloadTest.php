<?php

declare(strict_types=1);

use App\Enums\CheckoutMode;
use App\Models\PaymentGateway;
use App\Utilities\PaymentOptionPayload;

covers(PaymentOptionPayload::class);

uses()->group('utilities', 'payment');

test('exposes the gateway identity for every driver', function () {
    $gateway = PaymentGateway::factory()->cod()->create(['name' => 'Cash on Delivery']);

    expect(PaymentOptionPayload::fromGateway($gateway))
        ->toBe([
            'id' => $gateway->id,
            'name' => ['en' => 'Cash on Delivery'],
            'driver' => 'cod',
        ]);
});

test('exposes the public credential each embedded driver needs', function (string $state, string $key) {
    $gateway = PaymentGateway::factory()->{$state}()->create([
        'credentials' => [
            ...PaymentGateway::factory()->{$state}()->make()->credentials,
            'checkout_mode' => CheckoutMode::Embedded->value,
        ],
    ]);

    $payload = PaymentOptionPayload::fromGateway($gateway);

    expect($payload['checkout_mode'])->toBe(CheckoutMode::Embedded->value)
        ->and($payload[$key])->toBe($gateway->credentials[$key]);
})->with([
    'paypal' => ['paypal', 'client_id'],
    'razorpay' => ['razorpay', 'key_id'],
    'tap' => ['tap', 'public_key'],
    'paystack' => ['paystack', 'public_key'],
    'mercadopago' => ['mercadoPago', 'public_key'],
]);

test('never leaks a secret credential', function () {
    $gateway = PaymentGateway::factory()->stripe()->create([
        'credentials' => [
            'publishable_key' => 'pk_test_visible',
            'secret_key' => 'sk_test_secret',
            'checkout_mode' => CheckoutMode::Embedded->value,
        ],
    ]);

    expect(PaymentOptionPayload::fromGateway($gateway))
        ->not->toHaveKey('secret_key')
        ->and(PaymentOptionPayload::fromGateway($gateway)['publishable_key'])->toBe('pk_test_visible');
});

test('cod exposes no embedded fields even when marked embedded', function () {
    $gateway = PaymentGateway::factory()->cod()->create([
        'credentials' => ['checkout_mode' => CheckoutMode::Embedded->value],
    ]);

    expect(PaymentOptionPayload::fromGateway($gateway))
        ->not->toHaveKey('checkout_mode')
        ->and(array_keys(PaymentOptionPayload::fromGateway($gateway)))->toBe(['id', 'name', 'driver']);
});
