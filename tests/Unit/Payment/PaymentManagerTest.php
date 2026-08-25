<?php

declare(strict_types=1);

use App\Models\PaymentGateway;
use App\Payment\Drivers\CodDriver;
use App\Payment\Drivers\MockDriver;
use App\Payment\Drivers\PaypalDriver;
use App\Payment\Drivers\RazorpayDriver;
use App\Payment\Drivers\StripeDriver;
use App\Payment\PaymentManager;

covers(PaymentManager::class);

uses()->group('payment');

test('resolves cod driver for cod gateway', function () {
    $gateway = PaymentGateway::factory()->cod()->create(['is_active' => true]);

    $manager = new PaymentManager();
    $driver = $manager->driver($gateway);

    expect($driver)->toBeInstanceOf(CodDriver::class);
});

test('resolves paypal driver for paypal gateway', function () {
    $gateway = PaymentGateway::factory()->paypal()->create(['is_active' => true]);

    $manager = new PaymentManager();
    $driver = $manager->driver($gateway);

    expect($driver)->toBeInstanceOf(PaypalDriver::class);
});

test('resolves stripe driver for stripe gateway', function () {
    $gateway = PaymentGateway::factory()->stripe()->create(['is_active' => true]);

    $manager = new PaymentManager();
    $driver = $manager->driver($gateway);

    expect($driver)->toBeInstanceOf(StripeDriver::class);
});

test('resolves razorpay driver for razorpay gateway', function () {
    $gateway = PaymentGateway::factory()->razorpay()->create(['is_active' => true]);

    $manager = new PaymentManager();
    $driver = $manager->driver($gateway);

    expect($driver)->toBeInstanceOf(RazorpayDriver::class);
});

test('resolves different drivers for different gateways', function () {
    $codGateway = PaymentGateway::factory()->cod()->create(['is_active' => true]);
    $stripeGateway = PaymentGateway::factory()->stripe()->create(['is_active' => true]);

    $manager = new PaymentManager();
    $codDriver = $manager->driver($codGateway);
    $stripeDriver = $manager->driver($stripeGateway);

    expect($codDriver)->toBeInstanceOf(CodDriver::class)
        ->and($stripeDriver)->toBeInstanceOf(StripeDriver::class);
});

test('fake uses mock driver by default', function () {
    $gateway = PaymentGateway::factory()->stripe()->create(['is_active' => true]);

    $manager = PaymentManager::fake();

    expect($manager->driver($gateway))->toBeInstanceOf(MockDriver::class);
});

test('fake accepts a custom driver', function () {
    $gateway = PaymentGateway::factory()->stripe()->create(['is_active' => true]);
    $codDriver = new CodDriver();

    $manager = PaymentManager::fake($codDriver);

    expect($manager->driver($gateway))->toBe($codDriver);
});

test('fake registers itself in the container', function () {
    $manager = PaymentManager::fake();

    expect(app(PaymentManager::class))->toBe($manager);
});
