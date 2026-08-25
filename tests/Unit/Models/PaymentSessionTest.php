<?php

declare(strict_types=1);

use App\Enums\PaymentSessionPurpose;
use App\Enums\PaymentSessionStatus;
use App\Models\CheckoutSession;
use App\Models\PaymentGateway;
use App\Models\PaymentSession;
use Illuminate\Database\Eloquent\Factories\Factory;

covers(PaymentSession::class);

uses()->group('models', 'payment');

test('has factory', function () {
    expect(PaymentSession::factory())->toBeInstanceOf(Factory::class);
});

test('uses uuid primary key', function () {
    $gateway = PaymentGateway::factory()->create();
    $checkout = CheckoutSession::factory()->create(['payment_gateway_id' => $gateway->id]);
    $session = PaymentSession::factory()->create([
        'payment_gateway_id' => $gateway->id,
        'owner_type' => CheckoutSession::class,
        'owner_id' => $checkout->id,
    ]);

    expect($session->id)->toBeString()->toHaveLength(36);
});

test('casts purpose, status, amount, payload, metadata, and completed_at', function () {
    $gateway = PaymentGateway::factory()->create();
    $checkout = CheckoutSession::factory()->create(['payment_gateway_id' => $gateway->id]);
    $session = PaymentSession::factory()->completed()->create([
        'payment_gateway_id' => $gateway->id,
        'owner_type' => CheckoutSession::class,
        'owner_id' => $checkout->id,
        'amount' => '55.40',
        'purpose' => PaymentSessionPurpose::Checkout,
        'payload' => ['foo' => 'bar'],
        'metadata' => ['baz' => 1],
    ]);

    expect($session->purpose)->toBe(PaymentSessionPurpose::Checkout)
        ->and($session->status)->toBe(PaymentSessionStatus::Completed)
        ->and($session->amount)->toBe('55.4000')
        ->and($session->payload)->toBe(['foo' => 'bar'])
        ->and($session->metadata)->toBe(['baz' => 1])
        ->and($session->completed_at)->toBeInstanceOf(Carbon\CarbonImmutable::class);
});

test('belongs to payment gateway', function () {
    $gateway = PaymentGateway::factory()->create();
    $checkout = CheckoutSession::factory()->create(['payment_gateway_id' => $gateway->id]);
    $session = PaymentSession::factory()->create([
        'payment_gateway_id' => $gateway->id,
        'owner_type' => CheckoutSession::class,
        'owner_id' => $checkout->id,
    ]);

    expect($session->paymentGateway)->toBeInstanceOf(PaymentGateway::class)
        ->and($session->paymentGateway->id)->toBe($gateway->id);
});

test('morphs to checkout session owner', function () {
    $gateway = PaymentGateway::factory()->create();
    $checkout = CheckoutSession::factory()->create(['payment_gateway_id' => $gateway->id]);
    $session = PaymentSession::factory()->checkout()->create([
        'payment_gateway_id' => $gateway->id,
        'owner_type' => CheckoutSession::class,
        'owner_id' => $checkout->id,
    ]);

    expect($session->owner)->toBeInstanceOf(CheckoutSession::class)
        ->and($session->owner->id)->toBe($checkout->id);
});
