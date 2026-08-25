<?php

declare(strict_types=1);

namespace App\Enums;

use App\Models\PaymentGateway;
use App\Payment\Contracts\PaymentDriver;
use App\Payment\Drivers\CodDriver;
use App\Payment\Drivers\MercadoPagoDriver;
use App\Payment\Drivers\MollieDriver;
use App\Payment\Drivers\PaypalDriver;
use App\Payment\Drivers\PaystackDriver;
use App\Payment\Drivers\RazorpayDriver;
use App\Payment\Drivers\StripeDriver;
use App\Payment\Drivers\TapDriver;

enum PaymentGatewayDriver: string
{
    case Stripe = 'stripe';
    case Paypal = 'paypal';
    case Razorpay = 'razorpay';
    case Mollie = 'mollie';
    case Tap = 'tap';
    case Paystack = 'paystack';
    case MercadoPago = 'mercadopago';
    case Cod = 'cod';

    public function make(PaymentGateway $gateway): PaymentDriver
    {
        return match ($this) {
            self::Stripe => new StripeDriver($gateway),
            self::Paypal => new PaypalDriver($gateway),
            self::Razorpay => new RazorpayDriver($gateway),
            self::Mollie => new MollieDriver($gateway),
            self::Tap => new TapDriver($gateway),
            self::Paystack => new PaystackDriver($gateway),
            self::MercadoPago => new MercadoPagoDriver($gateway),
            self::Cod => new CodDriver(),
        };
    }
}
