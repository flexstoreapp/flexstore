<?php

declare(strict_types=1);

use App\Enums\PaymentGatewayDriver;
use App\Models\PaymentGateway;
use App\Payment\Drivers\CodDriver;
use App\Payment\Drivers\MercadoPagoDriver;
use App\Payment\Drivers\MollieDriver;
use App\Payment\Drivers\PaypalDriver;
use App\Payment\Drivers\PaystackDriver;
use App\Payment\Drivers\RazorpayDriver;
use App\Payment\Drivers\StripeDriver;
use App\Payment\Drivers\TapDriver;

covers(PaymentGatewayDriver::class);

uses()->group('enums', 'payment');

test('exposes every supported driver value', function () {
    expect(array_map(fn (PaymentGatewayDriver $case): string => $case->value, PaymentGatewayDriver::cases()))
        ->toBe(['stripe', 'paypal', 'razorpay', 'mollie', 'tap', 'paystack', 'mercadopago', 'cod']);
});

test('make builds the driver for each case', function (PaymentGatewayDriver $driver, string $expected) {
    $gateway = PaymentGateway::factory()->create(['driver' => $driver]);

    expect($driver->make($gateway))->toBeInstanceOf($expected);
})->with([
    [PaymentGatewayDriver::Stripe, StripeDriver::class],
    [PaymentGatewayDriver::Paypal, PaypalDriver::class],
    [PaymentGatewayDriver::Razorpay, RazorpayDriver::class],
    [PaymentGatewayDriver::Mollie, MollieDriver::class],
    [PaymentGatewayDriver::Tap, TapDriver::class],
    [PaymentGatewayDriver::Paystack, PaystackDriver::class],
    [PaymentGatewayDriver::MercadoPago, MercadoPagoDriver::class],
    [PaymentGatewayDriver::Cod, CodDriver::class],
]);
